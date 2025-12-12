<?php
// 1. Import Composer Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// 2. Use the Libraries
use Sentiment\Analyzer;
use Stichoza\GoogleTranslate\GoogleTranslate;

// Initialize variables
$student_name = "";
$text_behavior = "";
$text_teaching = "";
$result_behavior = null;
$result_teaching = null;

// Helper function to process text
function processFeedback($text) {
    if (empty($text)) return null;

    $tr = new GoogleTranslate();
    $analyzer = new Analyzer();
    
    try {
        // 1. Detect Language & Translate if needed
        $english_version = $tr->setSource(null)->setTarget('en')->translate($text);
        $detected_lang = $tr->getLastDetectedSource(); 
        
        $display_translation = "";
        $analysis_text = "";
        
        // Check if input is English
        if ($detected_lang == 'en' || $text == $english_version) {
            $display_translation = $tr->setSource('en')->setTarget('tl')->translate($text);
            $analysis_text = $text; 
        } else {
            $display_translation = $english_version;
            $analysis_text = $english_version;
        }

        // 2. Analyze Sentiment
        $analysis = $analyzer->getSentiment($analysis_text);
        
        // --- NEW: SENTIMENT BOOSTER LOGIC ---
        // This math reduces "Neutral" if there is a strong emotion detected.
        $pos = $analysis['pos'];
        $neg = $analysis['neg'];
        $neu = $analysis['neu'];
        $compound = $analysis['compound']; // Overall score (-1 to 1)

        // If the text is clearly emotional (compound score is not 0), boost the scores
        if (abs($compound) > 0.05) {
            // Take 60% of the Neutral score and give it to the dominant emotion
            $boost_amount = $neu * 0.60; 
            
            if ($neg > $pos) {
                // If it's mostly negative, give the boost to Negative
                $neg += $boost_amount;
                $neu -= $boost_amount;
            } elseif ($pos > $neg) {
                // If it's mostly positive, give the boost to Positive
                $pos += $boost_amount;
                $neu -= $boost_amount;
            }
        }
        // -------------------------------------

        // Update the scores array for display
        $scores = ['Positive' => $pos, 'Negative' => $neg, 'Neutral' => $neu];
        
        // Determine Dominant Category safely
        $dominant = 'Neutral';
        if ($pos > $neg && $pos > $neu) $dominant = 'Positive';
        elseif ($neg > $pos && $neg > $neu) $dominant = 'Negative';
        else $dominant = 'Neutral';

        return [
            'original' => $text,
            'translated' => $display_translation,
            'category' => $dominant,
            'scores' => $scores
        ];

    } catch (Exception $e) {
        return null; 
    }
}

// 3. Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = htmlspecialchars($_POST['student_name']);
    $text_behavior = $_POST['behavior_feedback'];
    $text_teaching = $_POST['teaching_feedback'];

    $result_behavior = processFeedback($text_behavior);
    $result_teaching = processFeedback($text_teaching);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SentimentAI - Comprehensive Analysis</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            color: #333;
        }
        .main-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: none;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .header-section {
            background-color: #f8f9fa;
            padding: 30px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .header-icon { font-size: 3rem; color: #764ba2; }
        
        .form-label { font-weight: 600; color: #555; font-size: 0.9rem; text-transform: uppercase; }
        .form-control:focus { border-color: #764ba2; box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.25); }
        
        .btn-analyze {
            background: linear-gradient(to right, #667eea, #764ba2);
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .btn-analyze:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }

        /* Result Cards */
        .result-card {
            border-radius: 15px;
            border: 1px solid #eee;
            padding: 20px;
            height: 100%;
            background: #fff;
            transition: transform 0.2s;
        }
        .result-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        
        .border-Positive { border-top: 5px solid #28a745; }
        .border-Negative { border-top: 5px solid #dc3545; }
        .border-Neutral { border-top: 5px solid #6c757d; }
        
        .text-Positive { color: #28a745; }
        .text-Negative { color: #dc3545; }
        .text-Neutral { color: #6c757d; }
        
        .progress { height: 8px; margin-bottom: 10px; }
        .score-label { font-size: 0.75rem; font-weight: 600; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="card main-card">
                <div class="header-section">
                    <i class="bi bi-robot header-icon"></i>
                    <h2 class="mt-3 fw-bold">Student Feedback AI</h2>
                    <p class="text-muted mb-0">Project Option A: Student Feedback Sentiment Analyzer</p>
                </div>
                
                <div class="card-body p-4">
                    <form method="POST" action="">
                        
                        <div class="mb-4">
                            <label class="form-label">Student Name (Optional)</label>
                            <input type="text" class="form-control" name="student_name" placeholder="Enter your name..." value="<?php echo $student_name; ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-primary"><i class="bi bi-person-heart"></i> Teacher's Behavior</label>
                                <textarea class="form-control" name="behavior_feedback" rows="4" placeholder="How does the teacher treat students? (e.g., Kind, Strict, Angry)" required><?php echo htmlspecialchars($text_behavior); ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-success"><i class="bi bi-book"></i> Teaching Method</label>
                                <textarea class="form-control" name="teaching_feedback" rows="4" placeholder="How well do they teach the subject? (e.g., Clear, Confusing, Fast)" required><?php echo htmlspecialchars($text_teaching); ?></textarea>
                            </div>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary btn-analyze text-white">
                                <i class="bi bi-stars"></i> Analyze Both Feedbacks
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($result_behavior && $result_teaching): ?>
                <div class="row fade-in">
                    <div class="col-12 mb-3 text-center">
                        <h4 class="fw-bold text-white">Analysis Results for: <u><?php echo !empty($student_name) ? $student_name : "Anonymous Student"; ?></u></h4>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="result-card border-<?php echo $result_behavior['category']; ?>">
                            <h5 class="text-center text-muted text-uppercase mb-3"><i class="bi bi-person-heart"></i> Behavior Analysis</h5>
                            
                            <div class="text-center mb-3">
                                <h2 class="fw-bold text-<?php echo $result_behavior['category']; ?>">
                                    <?php echo $result_behavior['category']; ?>
                                </h2>
                                <small class="text-muted fst-italic">"<?php echo htmlspecialchars($result_behavior['translated']); ?>"</small>
                            </div>

                            <div class="score-label"><span>Positive</span><span><?php echo number_format($result_behavior['scores']['Positive'] * 100, 1); ?>%</span></div>
                            <div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $result_behavior['scores']['Positive'] * 100; ?>%"></div></div>
                            
                            <div class="score-label"><span>Neutral</span><span><?php echo number_format($result_behavior['scores']['Neutral'] * 100, 1); ?>%</span></div>
                            <div class="progress"><div class="progress-bar bg-secondary" style="width: <?php echo $result_behavior['scores']['Neutral'] * 100; ?>%"></div></div>

                            <div class="score-label"><span>Negative</span><span><?php echo number_format($result_behavior['scores']['Negative'] * 100, 1); ?>%</span></div>
                            <div class="progress"><div class="progress-bar bg-danger" style="width: <?php echo $result_behavior['scores']['Negative'] * 100; ?>%"></div></div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="result-card border-<?php echo $result_teaching['category']; ?>">
                            <h5 class="text-center text-muted text-uppercase mb-3"><i class="bi bi-book"></i> Teaching Analysis</h5>
                            
                            <div class="text-center mb-3">
                                <h2 class="fw-bold text-<?php echo $result_teaching['category']; ?>">
                                    <?php echo $result_teaching['category']; ?>
                                </h2>
                                <small class="text-muted fst-italic">"<?php echo htmlspecialchars($result_teaching['translated']); ?>"</small>
                            </div>

                            <div class="score-label"><span>Positive</span><span><?php echo number_format($result_teaching['scores']['Positive'] * 100, 1); ?>%</span></div>
                            <div class="progress"><div class="progress-bar bg-success" style="width: <?php echo $result_teaching['scores']['Positive'] * 100; ?>%"></div></div>
                            
                            <div class="score-label"><span>Neutral</span><span><?php echo number_format($result_teaching['scores']['Neutral'] * 100, 1); ?>%</span></div>
                            <div class="progress"><div class="progress-bar bg-secondary" style="width: <?php echo $result_teaching['scores']['Neutral'] * 100; ?>%"></div></div>

                            <div class="score-label"><span>Negative</span><span><?php echo number_format($result_teaching['scores']['Negative'] * 100, 1); ?>%</span></div>
                            <div class="progress"><div class="progress-bar bg-danger" style="width: <?php echo $result_teaching['scores']['Negative'] * 100; ?>%"></div></div>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>