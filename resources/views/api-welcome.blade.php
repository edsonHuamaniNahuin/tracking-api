<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking API</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            margin: 2rem;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .status {
            background: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .endpoints {
            text-align: left;
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .endpoint {
            margin-bottom: 0.5rem;
            font-family: 'Courier New', monospace;
            color: #374151;
        }
        .method {
            background: #3b82f6;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }
        .footer {
            color: #9ca3af;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚢 Tracking API</h1>
        <p class="subtitle">Sistema de Seguimiento de Embarcaciones</p>

        <div class="status">✅ API Activa</div>

        <div class="endpoints">
            <h3>📋 Endpoints Principales:</h3>
            <div class="endpoint">
                <span class="method">POST</span>/api/v1/auth/login
            </div>
            <div class="endpoint">
                <span class="method">GET</span>/api/v1/vessels
            </div>
            <div class="endpoint">
                <span class="method">GET</span>/api/v1/dashboard/all-metrics
            </div>
            <div class="endpoint">
                <span class="method">GET</span>/api/docs (Swagger)
            </div>
        </div>

        <div class="footer">
            <p>Laravel {{ app()->version() }} | Entorno: {{ app()->environment() }}</p>
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
