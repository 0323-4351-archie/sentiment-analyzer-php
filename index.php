<?php
// 1. Import the Composer Autoloader (Loads the ML Library)
require_once __DIR__ . '/vendor/autoload.php';

// 2. Use the Sentiment Analyzer Library
use Sentiment\Analyzer;

$result = null;
$text = "";

// 3. Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['feedback_text'];
    
    if (!empty($text)) {
        // Initialize the ML Analyzer
        $analyzer = new Analyzer();
        
        // Process the text (Input -> Process -> Output)
        $analysis = $analyzer->getSentiment($text);
        
        // Determine the dominant sentiment
        $scores = [
            'Positive' => $analysis['pos'],
            'Negative' => $analysis['neg'],
            'Neutral'  => $analysis['neu']
        ];
        
        // Find the highest score
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
    <title>Student Feedback Sentiment Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .container { max-width: 600px; margin-top: 50px; }
        .card { padding: 20px; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .result-box { margin-top: 20px; padding: 15px; border-radius: 10px; }
        .bg-Positive { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .bg-Negative { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .bg-Neutral { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h3 class="text-center mb-4">📢 Sentiment Analyzer</h3>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="feedback" class="form-label">Enter Student Feedback:</label>
                <textarea class="form-control" name="feedback_text" id="feedback" rows="4" placeholder="e.g., The class was amazing and I learned a lot!" required><?php echo htmlspecialchars($text); ?></textarea>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Analyze Sentiment</button>
            </div>
        </form>

        <?php if ($result): ?>
            <div class="result-box bg-<?php echo $result['category']; ?>">
                <h4 class="text-center">Sentiment: <strong><?php echo $result['category']; ?></strong></h4>
                <hr>
                <small>Detailed Scores:</small>
                <ul>
                    <li>Positive: <?php echo $result['scores']['pos']; ?></li>
                    <li>Negative: <?php echo $result['scores']['neg']; ?></li>
                    <li>Neutral: <?php echo $result['scores']['neu']; ?></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>