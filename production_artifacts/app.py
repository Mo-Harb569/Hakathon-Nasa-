# -*- coding: utf-8 -*-
"""
Final Production API for ExoLife Discover
"""

import joblib
import os
import numpy as np
import pandas as pd
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from sklearn.preprocessing import LabelEncoder
from fastapi.middleware.cors import CORSMiddleware
from typing import List, Optional
import contextlib
import traceback 
import torch 
from pytorch_tabnet.tab_model import TabNetClassifier 
import io 
import torch.storage 

# =========================================================
# 0. الثوابت والمتغيرات العامة (Constants)
# =========================================================
FINAL_FEATURE_NAMES = [
    'koi_time0bk', 'ra', 'dec', 'koi_kepmag', 'default_flag', 'disposition', 
    'sy_snum', 'sy_pnum', 'discoverymethod', 'disc_year', 'disc_facility', 
    'soltype', 'pl_orbsmax', 'pl_orbeccen', 'ttv_flag', 'st_spectype', 
    'st_mass', 'st_met', 'st_metratio', 'sy_vmag', 'sy_kmag', 'sy_gaiamag', 
    'st_pmra', 'st_pmdec', 'pl_trandurh', 'pl_trandep', 'st_tmag', 
    'Orbital_Period', 'Planet_Radius_R_E', 'Planet_Mass_M_E', 
    'Equilibrium_Temp_K', 'Stellar_Teff_K', 'Planet_Density_Ratio'
]
NUM_FEATURES = len(FINAL_FEATURE_NAMES)
EXPORT_DIR = 'production_artifacts'
model_artifacts = {}
CLASS_LABELS = {0: 'CANDIDATE', 1: 'CONFIRMED', 2: 'FALSE POSITIVE'}

# =========================================================
# 1. تعريف نموذج الإدخال (Pydantic Schema)
# =========================================================
class PredictionInput(BaseModel):
    # جميع الميزات الـ 33 هنا 
    koi_time0bk: Optional[float] = None
    ra: Optional[float] = None
    dec: Optional[float] = None
    koi_kepmag: Optional[float] = None
    default_flag: Optional[float] = None
    disposition: Optional[str] = None
    sy_snum: Optional[float] = None
    sy_pnum: Optional[float] = None
    discoverymethod: Optional[str] = None
    disc_year: Optional[float] = None
    disc_facility: Optional[str] = None
    soltype: Optional[str] = None
    pl_orbsmax: Optional[float] = None
    pl_orbeccen: Optional[float] = None
    ttv_flag: Optional[float] = None
    st_spectype: Optional[str] = None
    st_mass: Optional[float] = None
    st_met: Optional[float] = None
    st_metratio: Optional[float] = None
    sy_vmag: Optional[float] = None
    sy_kmag: Optional[float] = None
    sy_gaiamag: Optional[float] = None
    st_pmra: Optional[float] = None
    st_pmdec: Optional[float] = None
    pl_trandurh: Optional[float] = None
    pl_trandep: Optional[float] = None
    st_tmag: Optional[float] = None
    Orbital_Period: Optional[float] = None
    Planet_Radius_R_E: Optional[float] = None
    Planet_Mass_M_E: Optional[float] = None
    Equilibrium_Temp_K: Optional[float] = None
    Stellar_Teff_K: Optional[float] = None
    Planet_Density_Ratio: Optional[float] = None


# =========================================================
# 2. تعريف دالة الـ Pipeline (المعالجة المُسبقة) - الأهم!
# =========================================================
def production_pipeline(raw_input_data, scaler_obj, pca_obj):
    # إنشاء DataFrame من بيانات الإدخال الخام
    df_processed = pd.DataFrame([raw_input_data])
    
    # 🚨 هندسة الميزات: حساب Planet_Density_Ratio 
    with contextlib.suppress(Exception): 
        mass = df_processed['Planet_Mass_M_E'].iloc[0]
        radius = df_processed['Planet_Radius_R_E'].iloc[0]
        
        # نحسب الكثافة فقط إذا كانت القيم منطقية وموجودة وغير صفر
        if mass is not None and radius is not None and radius != 0:
            df_processed['Planet_Density_Ratio'] = mass / (radius ** 3)
        
    # 1. التصفية: اختيار الأعمدة الـ 33 النهائية بالضبط (مهم للحفاظ على الترتيب)
    X_predict = df_processed.reindex(columns=FINAL_FEATURE_NAMES)
    
    # 2. ملء القيم المفقودة (Imputation): نملأ NaN بـ 0
    X_predict = X_predict.fillna(0) 

    # 3. ترميز فئوي (Label Encoding) للميزات النصية المتبقية
    categorical_cols = X_predict.select_dtypes(include=['object']).columns
    for col in categorical_cols:
        le = LabelEncoder()
        # نملأ أي قيمة مفقودة 'NaN' في الميزات الفئوية بكلمة 'missing' قبل الترميز
        X_predict[col] = le.fit_transform(X_predict[col].astype(str).fillna('missing'))

    # 4. التحجيم (Scaling)
    X_scaled_np = scaler_obj.transform(X_predict.values)
    
    # 5. تقليل الأبعاد (PCA)
    X_pca_np = pca_obj.transform(X_scaled_np).astype(np.float32)
    
    return X_pca_np


# =========================================================
# 3. إعداد الـ FastAPI و Lifespan (لتحميل المكونات)
# =========================================================

# تخزين الوظيفة الأصلية لـ PyTorch قبل الترقيع
original_load_from_bytes = torch.storage._load_from_bytes

# تعريف دالة الترقيع: تجبر التحميل على CPU
def _patched_load_from_bytes(b):
    # هذه الوظيفة يتم استدعاؤها داخليًا بواسطة joblib/pickle عند محاولة PyTorch
    # للتحميل من بايتات البيانات، هنا نضمن تمرير map_location='cpu'
    return torch.load(io.BytesIO(b), map_location=torch.device('cpu'), weights_only=False)

# تعريف دالة lifespan (تحميل المكونات عند بدء التشغيل)
@contextlib.asynccontextmanager
async def lifespan(app: FastAPI):
    global model_artifacts
    
    # الخطوة 1: تطبيق الترقيع البرمجي لضمان التحميل على CPU
    # هذا الإجراء ضروري لـ TabNet المحفوظ على GPU ويتم تحميله على CPU
    torch.storage._load_from_bytes = _patched_load_from_bytes
    
    try:
        # 🚨 تحميل نموذج TabNet باستخدام joblib.load
        # الترقيع أعلاه يضمن أن أي PyTorch load يتم داخل joblib سيستخدم CPU
        print("Attempting to load TabNet model...")
        model_artifacts['model'] = joblib.load(os.path.join(EXPORT_DIR, 'tabnet_model_tn2.joblib'))
        
        # تحميل Scaler و PCA باستخدام joblib.load
        print("Loading Scaler and PCA...")
        model_artifacts['scaler'] = joblib.load(os.path.join(EXPORT_DIR, 'scaler_for_api.joblib'))
        model_artifacts['pca'] = joblib.load(os.path.join(EXPORT_DIR, 'pca_for_api.joblib'))
        
        # ❌ تمت إزالة السطر model_artifacts['model'].model.eval()
        # لأن الكائن TabNetClassifier يُستخدم مباشرة ولا يحتوي على .model دائمًا
        
        print("🚀 All ML artifacts loaded successfully. Server is ready for predictions.")
    except Exception as e:
        # طباعة الخطأ الأصلي كاملاً
        traceback.print_exc() 
        print(f"Error loading ML artifacts: {e}")
        # رفع استثناء 500 في حالة فشل التحميل لإيقاف التشغيل
        raise HTTPException(status_code=500, detail="ML model components failed to load. Check server logs.")
    finally:
        # الخطوة 3: إعادة الوظيفة الأصلية بعد الانتهاء من التحميل
        torch.storage._load_from_bytes = original_load_from_bytes
        
    yield

# إنشاء تطبيق FastAPI
app = FastAPI(lifespan=lifespan, title="ExoLife Discover API", version="1.0.0")

# 🛡️ إضافة حماية CORS (للسماح بالاتصال من واجهة الويب)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"], 
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"]
)


# =========================================================
# 4. نقطة النهاية (Endpoint) /predict
# =========================================================

@app.post("/predict", summary="Predicts Exoplanet Status")
def predict(input_data: PredictionInput):
    # 1. التأكد من أن المكونات حُملت
    if not model_artifacts.get('model'):
        raise HTTPException(status_code=503, detail="Model server not ready or components failed to load.")
        
    # تحويل نموذج Pydantic إلى قاموس خام
    raw_dict = input_data.dict()
    
    try:
        # 2. تطبيق الـ Pipeline
        processed_features = production_pipeline(
            raw_dict,
            model_artifacts['scaler'],
            model_artifacts['pca']
        )
        
        # 3. التوقع (استخدام TabNet's native predict/predict_proba)
        prediction = model_artifacts['model'].predict(processed_features)
        probabilities = model_artifacts['model'].predict_proba(processed_features)
        
        # 4. بناء الرد
        predicted_class_index = int(prediction[0])
        
        response = {
            "status": "success",
            "predicted_class": CLASS_LABELS.get(predicted_class_index, "UNKNOWN"),
            "confidence_scores": {
                "CANDIDATE": f"{probabilities[0][0]*100:.2f}%",
                "CONFIRMED": f"{probabilities[0][1]*100:.2f}%",
                "FALSE_POSITIVE": f"{probabilities[0][2]*100:.2f}%"
            },
            "raw_prediction_index": predicted_class_index
        }
        
        return response
    
    except Exception as e:
        print(f"Prediction processing failed: {e}")
        raise HTTPException(status_code=500, detail=f"Prediction processing error: {e}")

# =========================================================
# 5. نقطة النهاية (Endpoint) /
# =========================================================
@app.get("/")
def read_root():
    return {"message": "ExoLife Discover API is running! Access /docs for endpoints."}
