<?php
// 1. Import Composer Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// 2. Use the Libraries
use Sentiment\Analyzer;
use Stichoza\GoogleTranslate\GoogleTranslate;

$result = null;
$text = "";
$translated_text = "";
$detected_language = "";
$target_lang_name = "";

// 3. Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['feedback_text']);
    
    if (!empty($text)) {
        try {
            // --- NEW: TRANSLATION LOGIC ---
            $tr = new GoogleTranslate(); // Initialize Translator
            
            // 1. Detect the Language (Is it 'en', 'tl', etc.?)
            // We temporarily translate to English to see what language it was
            $english_version = $tr->setSource(null)->setTarget('en')->translate($text);
            $detected_language = $tr->getLastDetectedSource(); // Get the code (e.g., 'tl', 'en')

            // 2. Handle Translation Logic based on your request
            // Check if it's English (or very close to it)
            if ($detected_language === 'en' || str_starts_with($detected_language, 'en')) {
                // If input is English -> Translate to Tagalog for display
                $display_translation = $tr->setSource('en')->setTarget('tl')->translate($text);
                $text_to_analyze = $text; // Analyze the original English
                $target_lang_name = "Tagalog";
                $detected_language = "English"; // Set nice name for display
            } else {
                // If input is Tagalog (or other) -> Translate to English for Analysis
                $display_translation = $english_version; // Show the English version
                $text_to_analyze = $english_version; // Analyze the English version
                $target_lang_name = "English";
                 // If it detected 'tl', make it look nicer
                 if ($detected_language === 'tl') { $detected_language = "Tagalog"; }
            }

            $translated_text = $display_translation;

            // --- SENTIMENT ANALYSIS (Always analyzes English text) ---
            $analyzer = new Analyzer();
            $analysis = $analyzer->getSentiment($text_to_analyze);
            
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

        } catch (Exception $e) {
            // If translation fails (no internet/API issue), fallback to analyzing original text
            // This prevents the site from crashing if Google is down
            try {
                $analyzer = new Analyzer();
                $analysis = $analyzer->getSentiment($text);
                $scores = ['Positive' => $analysis['pos'], 'Negative' => $analysis['neg'], 'Neutral'  => $analysis['neu']];
                $dominant = array_keys($scores, max($scores))[0];
                $result = ['category' => $dominant, 'scores' => $analysis];
                $translated_text = "Translation unavailable - Analyzing original text.";
                $detected_language = "Unknown";
                $target_lang_name = "N/A";
            } catch (Exception $e2) {
                 $text = "Error analyzing text.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SentimentAI - Multilingual Analyzer</title>
    
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
        .header-icon { font-size: 3rem; color: #764ba2; }
        .form-section { padding: 30px; }
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
        .result-container {
            background-color: #fff;
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
            border: 1px solid #eee;
        }
        .translation-box {
            background-color: #e2e3e5;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #764ba2;
        }
        /* Dynamic Colors */
        .border-Positive { border-left: 5px solid #28a745; background-color: #f0fff4; }
        .border-Negative { border-left: 5px solid #dc3545; background-color: #fff5f5; }
        .border-Neutral { border-left: 5px solid #6c757d; background-color: #f8f9fa; }
        .text-Positive { color: #28a745; }
        .text-Negative { color: #dc3545; }
        .text-Neutral { color: #6c757d; }
        .progress { height: 10px; border-radius: 5px; margin-bottom: 15px; background-color: #e9ecef; }
        .score-label { font-size: 0.85rem; font-weight: 600; display: flex; justify-content: space-between; margin-bottom: 5px; }
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
    <p class="text-muted mb-0">Project Option A: Student Feedback Sentiment Analyzer</p>
                </div>

                <div class="form-section">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="feedback" class="form-label text-uppercase fw-bold text-muted" style="font-size: 0.8rem;">Input Text (English or Tagalog)</label>
                            <textarea class="form-control" name="feedback_text" id="feedback" rows="4" placeholder="Type here... (e.g., 'Magandang araw' or 'Good day')" required><?php echo htmlspecialchars($text); ?></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-analyze">
                                <i class="bi bi-magic"></i> Translate & Analyze
                            </button>
                        </div>
                    </form>

                    <?php if ($result): ?>
                        <div class="result-container border-<?php echo $result['category']; ?> fade-in">
                            
                            <?php if (!empty($translated_text) && $translated_text !== "Translation unavailable - Analyzing original text."): ?>
                            <div class="translation-box">
                                <small class="text-uppercase fw-bold text-muted">Detected: <?php echo strtoupper(htmlspecialchars($detected_language)); ?> <i class="bi bi-arrow-right"></i> <?php echo htmlspecialchars($target_lang_name); ?></small>
                                <p class="mb-0 mt-1 fst-italic">"<?php echo htmlspecialchars($translated_text); ?>"</p>
                            </div>
                            <?php endif; ?>

                            <div class="text-center mb-4">
                                <h6 class="text-muted text-uppercase mb-1">Detected Tone</h6>
                                <h2 class="fw-bold text-<?php echo $result['category']; ?>">
                                    <?php 
                                        if($result['category'] == 'Positive') echo '<i class="bi bi-emoji-smile-fill"></i> Positive';
                                        elseif($result['category'] == 'Negative') echo '<i class="bi bi-emoji-frown-fill"></i> Negative';
                                        else echo '<i class="bi bi-emoji-neutral-fill"></i> Neutral';
                                    ?>
                                </h2>
                            </div>

                            <hr class="opacity-25">

                            <div class="mt-3">
                                <div class="score-label"><span>Positive</span><span><?php echo round($result['scores']['pos'] * 100); ?>%</span></div>
                                <div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $result['scores']['pos'] * 100; ?>%"></div></div>

                                <div class="score-label"><span>Neutral</span><span><?php echo round($result['scores']['neu'] * 100); ?>%</span></div>
                                <div class="progress"><div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $result['scores']['neu'] * 100; ?>%"></div></div>

                                <div class="score-label"><span>Negative</span><span><?php echo round($result['scores']['neg'] * 100); ?>%</span></div>
                                <div class="progress"><div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $result['scores']['neg'] * 100; ?>%"></div></div>
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