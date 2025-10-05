{{-- resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="en" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/three.js']) 
    <title>ExoLife Discover</title>
    <style>
        .hidden { display: none !important; } 
    </style>
</head>
<body 
    data-current-planet-id="{{ $planet->id ?? 0 }}"
    data-is-first="{{ $isFirst ?? true }}" 
    data-is-last="{{ $isLast ?? true }}"
>
    
    <div class="navigation-controls">
        <button id="next-target-btn">
            <span class="icon">❮</span> next
        </button>
        
        <button id="prev-target-btn" disabled>
            previous <span class="icon">❯</span>
        </button>
    </div>
    
    <div class="main-container" dir="ltr">
        <header class="header">
            <div class="logo">
                <h1>ExoLife Discover</h1>
            </div>
            <h1>{{ $planet->name ?? 'Habitability Index' }}</h1>
        </header>
        
        <div class="main-content">
            <div class="data-panel">

                {{-- الملخص --}}
                <div id="summary-view">
                    <h2>{{ $planet->name ?? 'Name of the planet' }}</h2> 
                    <div class="habitability-score">
                        <div class="score-circle">{{ $score_percentage ?? 0 }}%</div>
                        <div class="score-text">
                            <span>Habitability Score</span>
                            <span>{{ number_format($score ?? 0, 1) }} / 100</span> 
                        </div>
                    </div>
                    <div class="details">
                        <p><span class="icon">🪐</span> Type: {{ $planet_type ?? 'undefined' }}</p>
                        <p><span class="icon">🚀</span> Distance: {{ $planet_distance ?? 'N/A' }} light year</p>
                        <p><span class="icon">📅</span> Rotation Period: {{ $planet_orbital_period ?? 'N/A' }} day</p>
                        <p><span class="icon">🔬</span> ML Classification: {{ $ml_disposition ?? 'N/A' }}</p>
                        <p><span class="icon">✨</span> Condition: {{ $status_text ?? 'N/A' }}</p>
                    </div>
                    
                    <div class="buttons">
                        <button onclick="toggleView('details-view')">View detailed data</button>
                        <button onclick="toggleView('prediction-input-view')">New planet analysis</button>
                    </div>
                </div>

                {{-- نموذج التحليل --}}
                <div id="prediction-input-view" class="hidden">
                    <h2>Enter planetary properties for analysis</h2>
                    <form id="analysis-form" class="input-form">
                        <p>يرجى إدخال قيم الخصائص السبع الرئيسية للتحليل:</p>

                        <label>Orbital Period</label>
                        <input type="number" name="orbital_period" step="0.001" required>

                        <label>Planet Radius</label>
                        <input type="number" name="planet_radius" step="0.01" required>
                        
                        <label>Planet Mass</label>
                        <input type="number" name="planet_mass" step="0.01" required>
                        
                        <label>Distance</label>
                        <input type="number" name="distance" step="0.01" required>

                        <label>Surface Temperature (K)</label>
                        <input type="number" name="surface_temperature" step="0.01" required>
                        
                        <label>Atmosphere Pressure</label>
                        <input type="number" name="atmosphere_pressure" step="0.01" required>

                        <label>Orbital Eccentricity</label>
                        <input type="number" name="orbital_eccentricity" step="0.001" required>

                        <button type="submit" id="analyze-btn">Start analysis</button>
                    </form>
                    
                    <div class="buttons">
                        <button onclick="toggleView('summary-view')">Back</button>
                    </div>
                </div>

                {{-- تفاصيل الكوكب --}}
                <div id="details-view" class="hidden">
                    <h2>تفاصيل الكوكب</h2>
                    <div class="scroll-table">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>column</th>
                                    <th>value</th>
                                </tr>
                            </thead>
                            <tbody id="details-table-body">
                                {{-- سيتم تعبئتها تلقائياً بواسطة JS عند التنقل أو التحليل --}}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="buttons">
                        <button onclick="toggleView('summary-view')">Back</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script>
        function toggleView(showId) {
            const views = ["summary-view", "details-view", "prediction-input-view"];
            views.forEach(id => {
                const el = document.getElementById(id);
                if(el) el.classList.add("hidden");
            });
            const showEl = document.getElementById(showId);
            if(showEl) showEl.classList.remove("hidden");
        }
    </script>
</body>
</html>
