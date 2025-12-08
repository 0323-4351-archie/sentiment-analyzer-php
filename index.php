<?php
// 1. Import the Composer Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// 2. Use the Sentiment Analyzer Library
use Sentiment\Analyzer;

$result = null;
$text = "";

// 3. Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['feedback_text'];
    
    if (!empty($text)) {
        $analyzer = new Analyzer();
        $analysis = $analyzer->getSentiment($text);
        
        $scores = [
            'Positive' => $analysis['pos'],
            'Negative' => $analysis['neg'],
            'Neutral'  => $analysis['neu']
        ];
        
        $dominant = array_keys($scores, max($scores))[0];
        $result = [
            'category' => $dominant,
            'scores' => $analysis
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SentimentAI - Student Feedback Analysis</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .main-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: none;
            overflow: hidden;
        }
        .header-section {
            background-color: #f8f9fa;
            padding: 30px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .header-icon {
            font-size: 3rem;
            color: #764ba2;
        }
        .form-section {
            padding: 30px;
        }
        textarea {
            resize: none;
            border-radius: 10px !important;
            border: 2px solid #e9ecef !important;
            transition: all 0.3s;
        }
        textarea:focus {
            border-color: #764ba2 !important;
            box-shadow: 0 0 10px rgba(118, 75, 162, 0.1) !important;
        }
        .btn-analyze {
            background: linear-gradient(to right, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: transform 0.2s;
        }
        .btn-analyze:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
        }

        /* Result Styling */
        .result-container {
            background-color: #fff;
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
            border: 1px solid #eee;
        }
        
        /* Dynamic Colors for Dominant Result */
        .border-Positive { border-left: 5px solid #28a745; background-color: #f0fff4; }
        .border-Negative { border-left: 5px solid #dc3545; background-color: #fff5f5; }
        .border-Neutral { border-left: 5px solid #6c757d; background-color: #f8f9fa; }

        .text-Positive { color: #28a745; }
        .text-Negative { color: #dc3545; }
        .text-Neutral { color: #6c757d; }

        /* Progress Bars */
        .progress {
            height: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            background-color: #e9ecef;
        }
        .score-label {
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card main-card">
                
                <div class="header-section">
                    <i class="bi bi-robot header-icon"></i>
                    <h2 class="mt-3 fw-bold">Sentiment AI</h2>
                    <p class="text-muted mb-0">Analyze student feedback instantly</p>
                </div>

                <div class="form-section">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="feedback" class="form-label text-uppercase fw-bold text-muted" style="font-size: 0.8rem;">Input Text</label>
                            <textarea class="form-control" name="feedback_text" id="feedback" rows="4" placeholder="Paste student feedback here..." required><?php echo htmlspecialchars($text); ?></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-analyze">
                                <i class="bi bi-magic"></i> Analyze Sentiment
                            </button>
                        </div>
                    </form>

                    <?php if ($result): ?>
                        <div class="result-container border-<?php echo $result['category']; ?> fade-in">
                            
                            <div class="text-center mb-4">
                                <h6 class="text-muted text-uppercase mb-1">Detected Tone</h6>
                                <h2 class="fw-bold text-<?php echo $result['category']; ?>">
                                    <?php 
                                        // Add an icon based on category
                                        if($result['category'] == 'Positive') echo '<i class="bi bi-emoji-smile-fill"></i> Positive';
                                        elseif($result['category'] == 'Negative') echo '<i class="bi bi-emoji-frown-fill"></i> Negative';
                                        else echo '<i class="bi bi-emoji-neutral-fill"></i> Neutral';
                                    ?>
                                </h2>
                            </div>

                            <hr class="opacity-25">

                            <div class="mt-3">
                                <div class="score-label">
                                    <span>Positive</span>
                                    <span><?php echo round($result['scores']['pos'] * 100); ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $result['scores']['pos'] * 100; ?>%"></div>
                                </div>

                                <div class="score-label">
                                    <span>Neutral</span>
                                    <span><?php echo round($result['scores']['neu'] * 100); ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $result['scores']['neu'] * 100; ?>%"></div>
                                </div>

                                <div class="score-label">
                                    <span>Negative</span>
                                    <span><?php echo round($result['scores']['neg'] * 100); ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $result['scores']['neg'] * 100; ?>%"></div>
                                </div>
                            </div>

                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <div class="text-center mt-3 text-white opacity-75">
                <small>&copy; <?php echo date('Y'); ?> Student Feedback Analyzer Project</small>
            </div>

        </div>
    </div>
</div>

</body>
</html>