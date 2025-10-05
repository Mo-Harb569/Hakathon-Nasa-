import { loadTargetModel } from './three.js'; 

// عناصر DOM
const prevBtn = document.getElementById('prev-target-btn');
const nextBtn = document.getElementById('next-target-btn');
const analysisForm = document.getElementById('analysis-form');
const analyzeBtn = document.getElementById('analyze-btn');

const navigateApiUrl = '/api/navigate-target/'; 
const analyzeApiUrl = '/api/analyze-new';

let currentPlanetId = parseInt(document.body.dataset.currentPlanetId) || 0;
let isFirst = document.body.dataset.isFirst === 'true';
let isLast = document.body.dataset.isLast === 'true';

// --------------------------------------------------------
// تحديث أزرار التنقل
// --------------------------------------------------------
function updateNavigationButtons() {
    if (prevBtn) prevBtn.disabled = isFirst;
    if (nextBtn) nextBtn.disabled = isLast;
}

// --------------------------------------------------------
// تحديث واجهة المستخدم
// --------------------------------------------------------

window.currentPlanetData = {};

const dataAttr = document.body.dataset.currentPlanet;
if (dataAttr) {
    try {
        window.currentPlanetData = JSON.parse(dataAttr);
    } catch (e) {
        console.error('❌ خطأ في قراءة بيانات الكوكب الأول:', e);
    }
}
function updateUI(planetData) {
    currentPlanetId = planetData.id;
    isFirst = planetData.isFirst;
    isLast = planetData.isLast;
    updateNavigationButtons();

    const planetName = planetData.name || 'كوكب جديد مُحلل';

    const headerTitle = document.querySelector('.header h1:last-child');
    if (headerTitle) headerTitle.textContent = planetName;
    const summaryTitle = document.querySelector('#summary-view h2');
    if (summaryTitle) summaryTitle.textContent = planetName;

    const score = planetData.habitabilityScore || 0; 
    const scorePercentage = Math.round(score * 1);
    const scoreFormatted = score.toFixed(1);

    let status_text = 'Uncertain/Low';
    if (score >= 7.5) status_text = 'Habitability Score';
    else if (score >= 5.0) status_text = 'potentially livable';

    const scoreCircle = document.querySelector('.habitability-score .score-circle');
    if (scoreCircle) scoreCircle.textContent = `${scorePercentage}%`;
    const scoreSpan = document.querySelector('.habitability-score .score-text span:last-child');
    if (scoreSpan) scoreSpan.textContent = `${scoreFormatted} / 100`;

    const details = document.querySelector('#summary-view .details');
    if (details) {
        details.innerHTML = `
            <p><span class="icon">🪐</span> Type: ${planetData.planetType || 'undefined'}</p>
            <p><span class="icon">🚀</span> distance: ${planetData.distance || 'N/A'} light year</p>
            <p><span class="icon">📅</span> Rotation Period: ${planetData.orbitalPeriod || 'N/A'} day</p>
            <p><span class="icon">🔬</span> ML Classification: ${planetData.mlDisposition || 'N/A'}</p>
            <p><span class="icon">✨</span> Condition: ${status_text}</p>
        `;
    }

    // تعبئة جدول التفاصيل
    const tableBody = document.querySelector('#details-table-body'); 
    if (tableBody) {
        const dataForTable = { ...planetData };
        delete dataForTable.id;
        delete dataForTable.createdAt;
        delete dataForTable.updatedAt;
        delete dataForTable.isFirst; 
        delete dataForTable.isLast;

        dataForTable['habitabilityScore'] = scoreFormatted;

        let tableContent = '';
        for (const [key, value] of Object.entries(dataForTable)) {
            const toReadable = (str) => {
                const map = {
                    'habitabilityScore': 'Habitability Score (Calculated)',
                    'mlDisposition': 'ML Disposition (Classification)',
                    'planetType': 'Planet Type'
                };
                return map[str] || str.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase());
            };
            tableContent += `
                <tr>
                    <td>${toReadable(key)}</td>
                    <td>${value === null || value === undefined ? 'N/A' : value}</td>
                </tr>
            `;
        }
        tableBody.innerHTML = tableContent || '<tr><td colspan="2">No data for the planet</td></tr>';
    }

    // ** تحديث نموذج الكوكب الثلاثي الأبعاد **
     loadTargetModel({
     physicalType: (planetData.planetType || 'rock').toLowerCase(),
     planetRadius: planetData.planetRadius
 });


    if (typeof toggleView === 'function') toggleView('summary-view');

    console.log(`✅ تم تحديث العرض: ${planetName} (ID: ${currentPlanetId})`);
}


// --------------------------------------------------------
// التنقل بين الكواكب
// --------------------------------------------------------
async function navigatePlanet(direction) {
    if (prevBtn) prevBtn.disabled = true;
    if (nextBtn) nextBtn.disabled = true;

    try {
        const response = await fetch(`${navigateApiUrl}${currentPlanetId}/${direction}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const planetData = await response.json();
        updateUI(planetData);
        
    } catch (error) {
        console.error('❌ خطأ في عملية التنقل:', error);
    } finally {
        updateNavigationButtons(); 
    }
}

// --------------------------------------------------------
// تحليل الكوكب الجديد
// --------------------------------------------------------
async function handleAnalysisSubmit(e) {
    e.preventDefault();

    if (analyzeBtn) {
        analyzeBtn.disabled = true;
        analyzeBtn.textContent = 'Analyzing...';
    }

    const formData = new FormData(analysisForm);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch(analyzeApiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });

        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (jsonErr) {
            console.error('❌ السيرفر لم يرجع JSON صالح:', text);
            alert('حدث خطأ في الخادم. تحقق من وحدة التحكم.');
            return;
        }

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}, ${JSON.stringify(result)}`);

        console.log('✅ تم التحليل والحفظ بنجاح:', result);
        updateUI(result);

    } catch (error) {
        console.error('❌ خطأ عام أثناء التحليل:', error);
        alert('حدث خطأ أثناء معالجة التحليل. راجع وحدة التحكم.');
    } finally {
        if (analyzeBtn) {
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = 'Start analysis';
        }
    }
}

// --------------------------------------------------------
// ربط الأحداث
// --------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    updateNavigationButtons();
    if (prevBtn) prevBtn.addEventListener('click', () => navigatePlanet('prev'));
    if (nextBtn) nextBtn.addEventListener('click', () => navigatePlanet('next'));
    if (analysisForm) analysisForm.addEventListener('submit', handleAnalysisSubmit);
    if (typeof toggleView === 'function') toggleView('summary-view');
});
