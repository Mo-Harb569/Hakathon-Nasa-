from fastapi import FastAPI
from pydantic import BaseModel
from pytorch_tabnet.tab_model import TabNetClassifier  # أو TabNetRegressor حسب موديلك
import torch
import os

app = FastAPI(title="Planet AI API")

MODEL_PATH = "tabnet_model_tn2.joblib"
if not os.path.exists(MODEL_PATH):
    raise FileNotFoundError(f"الموديل مش موجود: {MODEL_PATH}")

# إنشاء الموديل TabNet
model = TabNetClassifier()  # أو TabNetRegressor() حسب الموديل تبعك

# تحميل الموديل على CPU
model.load_model(MODEL_PATH)  # TabNet عنده load_model الخاص

# هيكل البيانات اللي رح يستقبلها Laravel
class PlanetData(BaseModel):
    feature1: float
    feature2: float
    feature3: float

@app.get("/")
def root():
    return {"message": "API جاهز"}

@app.post("/predict")
def predict(data: PlanetData):
    try:
        X = [[data.feature1, data.feature2, data.feature3]]
        result = model.predict(X)
        return {"prediction": float(result[0])}
    except Exception as e:
        return {"error": str(e)}
