<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$exam_id = $_GET['exam_id'];

// Check if user has already completed this exam
$check_completed_sql = "SELECT id FROM exam_results WHERE user_id = ? AND exam_id = ?";
$check_completed_stmt = $conn->prepare($check_completed_sql);
$check_completed_stmt->bind_param("ii", $user_id, $exam_id);
$check_completed_stmt->execute();
$completed_result = $check_completed_stmt->get_result();

if ($completed_result->num_rows > 0) {
    // Exam already completed, redirect to reports or show a message
    header("Location: my_reports.php?message=Exam already completed.");
    exit();
}

// Fetch exam details
$exam_sql = "SELECT title, duration_in_minutes FROM exams WHERE id = ?";
$exam_stmt = $conn->prepare($exam_sql);
$exam_stmt->bind_param("i", $exam_id);
$exam_stmt->execute();
$exam_result = $exam_stmt->get_result();
$exam = $exam_result->fetch_assoc();

// Fetch questions and options for the exam
$questions_sql = "SELECT q.id AS question_id, q.question_text, q.question_image_path, q.score, o.id AS option_id, o.option_text, o.option_image_path
                  FROM questions q
                  JOIN options o ON q.id = o.question_id
                  WHERE q.exam_id = ? ORDER BY q.id, o.id";
$questions_stmt = $conn->prepare($questions_sql);
$questions_stmt->bind_param("i", $exam_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();

$questions = [];
$total_possible_score = 0;
while ($row = $questions_result->fetch_assoc()) {
    if (!isset($questions[$row['question_id']])) {
        $questions[$row['question_id']]['question_text'] = $row['question_text'];
        $questions[$row['question_id']]['question_image_path'] = $row['question_image_path'];
        $questions[$row['question_id']]['score'] = $row['score'];
        $questions[$row['question_id']]['options'] = [];
        $total_possible_score += $row['score']; // Sum up total possible score
    }
    $questions[$row['question_id']]['options'][] = [
        'option_id' => $row['option_id'],
        'option_text' => $row['option_text'],
        'option_image_path' => $row['option_image_path']
    ];
}

$question_ids = array_keys($questions);
$total_questions = count($question_ids);

// Handle exam submission (remains largely the same, but now triggered by JS)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_exam'])) {
    $user_total_score = 0;
    $attempted_questions_count = 0;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'question_') === 0) {
            $question_id = str_replace('question_', '', $key);
            $selected_option_id = $value;

            // Increment attempted questions count
            $attempted_questions_count++;

            // Save user answer
            $insert_answer_sql = "INSERT INTO user_exam_answers (user_id, exam_id, question_id, selected_option_id) VALUES (?, ?, ?, ?)";
            $insert_answer_stmt = $conn->prepare($insert_answer_sql);
            $insert_answer_stmt->bind_param("iiii", $user_id, $exam_id, $question_id, $selected_option_id);
            $insert_answer_stmt->execute();

            // Check if answer is correct and add question's score
            $check_correct_sql = "SELECT o.is_correct, q.score FROM options o JOIN questions q ON o.question_id = q.id WHERE o.id = ? AND o.question_id = ?";
            $check_correct_stmt = $conn->prepare($check_correct_sql);
            $check_correct_stmt->bind_param("ii", $selected_option_id, $question_id);
            $check_correct_stmt->execute();
            $correct_result = $check_correct_stmt->get_result();
            $correct_row = $correct_result->fetch_assoc();

            if ($correct_row['is_correct'] == 1) {
                $user_total_score += $correct_row['score'];
            }
        }
    }

    // Calculate percentage score based on total possible score
    $percentage_score = ($total_possible_score > 0) ? ($user_total_score / $total_possible_score) * 100 : 0;

    // Save exam result
    $insert_result_sql = "INSERT INTO exam_results (user_id, exam_id, score) VALUES (?, ?, ?)";
    $insert_result_stmt = $conn->prepare($insert_result_sql);
    $insert_result_stmt->bind_param("iid", $user_id, $exam_id, $percentage_score);
    $insert_result_stmt->execute();

    header("Location: my_reports.php"); // Redirect to reports page after submission
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam: <?php echo $exam['title']; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .question-container {
            display: none; /* Hide all questions by default */
        }
        .question-container.active {
            display: block; /* Show active question */
        }
        .exam-navigation {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .exam-navigation button {
            width: auto;
            padding: 10px 20px;
        }
        .exam-layout {
            display: flex;
            gap: 20px;
        }
        .exam-sidebar {
            width: 200px;
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            align-self: flex-start; /* Stick to the top */
        }
        .exam-main-content {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>User Panel</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_courses.php">My Courses</a></li>
                <li><a href="my_exams.php">My Exams</a></li>
                <li><a href="my_reports.php">My Reports</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <h2><?php echo $exam['title']; ?></h2>
            </header>
            <main>
                <div class="exam-layout">
                    <div class="exam-sidebar">
                        <div id="timer">Time Left: --:--</div>
                        <p>Question <span id="current-question-number">1</span> of <?php echo $total_questions; ?></p>
                    </div>
                    <div class="exam-main-content">
                        <form id="exam-form" action="" method="post">
                            <?php $q_index = 0; foreach ($questions as $question_id => $question_data): ?>
                            <div class="question-container" id="question-<?php echo $q_index; ?>">
                                <h3><?php echo ($q_index + 1) . ". " . $question_data['question_text']; ?> (Score: <?php echo $question_data['score']; ?>)</h3>
                                <?php if ($question_data['question_image_path']): ?>
                                <img src="<?php echo $question_data['question_image_path']; ?>" width="200">
                                <?php endif; ?>
                                <div class="options">
                                    <?php foreach ($question_data['options'] as $option): ?>
                                    <label>
                                        <input type="radio" name="question_<?php echo $question_id; ?>" value="<?php echo $option['option_id']; ?>" >
                                        <?php echo $option['option_text']; ?>
                                        <?php if ($option['option_image_path']): ?>
                                        <img src="<?php echo $option['option_image_path']; ?>" width="50">
                                        <?php endif; ?><br>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php $q_index++; endforeach; ?>

                            <div class="exam-navigation">
                                <button type="button" id="skip-button">Skip</button>
                                <button type="button" id="next-button">Next</button>
                                <button type="submit" name="submit_exam" id="submit-button" style="display:none;">Submit Exam</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        let timeLeft = <?php echo $exam['duration_in_minutes'] * 60; ?>;
        let timerInterval;
        let currentQuestionIndex = 0;
        const questions = document.querySelectorAll('.question-container');
        const totalQuestions = questions.length;
        const nextButton = document.getElementById('next-button');
        const skipButton = document.getElementById('skip-button');
        const submitButton = document.getElementById('submit-button');
        const currentQuestionNumberSpan = document.getElementById('current-question-number');

        function showQuestion(index) {
            questions.forEach((q, i) => {
                if (i === index) {
                    q.classList.add('active');
                } else {
                    q.classList.remove('active');
                }
            });
            currentQuestionNumberSpan.textContent = index + 1;

            if (index === totalQuestions - 1) {
                nextButton.style.display = 'none';
                skipButton.style.display = 'none';
                submitButton.style.display = 'block';
            } else {
                nextButton.style.display = 'block';
                skipButton.style.display = 'block';
                submitButton.style.display = 'none';
            }
        }

        function startTimer() {
            timerInterval = setInterval(function() {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;

                document.getElementById('timer').textContent = `Time Left: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('exam-form').submit(); // Auto-submit when time runs out
                }
                timeLeft--;
            }, 1000);
        }

        nextButton.addEventListener('click', () => {
            if (currentQuestionIndex < totalQuestions - 1) {
                currentQuestionIndex++;
                showQuestion(currentQuestionIndex);
            }
        });

        skipButton.addEventListener('click', () => {
            if (currentQuestionIndex < totalQuestions - 1) {
                currentQuestionIndex++;
                showQuestion(currentQuestionIndex);
            }
        });

        window.onload = () => {
            showQuestion(currentQuestionIndex);
            startTimer();
        };
    </script>
</body>
</html>