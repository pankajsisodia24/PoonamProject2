<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch assigned courses with their completion status
$sql = "SELECT c.id, c.title, c.description, c.category, uc.status 
        FROM user_courses uc 
        JOIN courses c ON uc.course_id = c.id 
        WHERE uc.user_id = ? ORDER BY c.category, c.title";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses_result = $stmt->get_result();

$courses_data = [];
while ($course = $courses_result->fetch_assoc()) {
    $course_id = $course['id'];
    $course_category = $course['category'] ?: 'Uncategorized';

    // Fetch materials for this course
    $materials_sql = "SELECT id, file_name, file_type, file_path, section_name FROM course_materials WHERE course_id = ? ORDER BY section_name, file_name";
    $materials_stmt = $conn->prepare($materials_sql);
    $materials_stmt->bind_param("i", $course_id);
    $materials_stmt->execute();
    $materials_res = $materials_stmt->get_result();

    $sections = [];
    $total_materials_in_course = 0;
    $total_viewed_materials_in_course = 0;

    while ($material = $materials_res->fetch_assoc()) {
        $section_name = $material['section_name'] ?: 'Uncategorized';
        if (!isset($sections[$section_name])) {
            $sections[$section_name] = [
                'total_materials' => 0,
                'viewed_materials' => 0,
                'materials' => []
            ];
        }
        $sections[$section_name]['total_materials']++;
        $total_materials_in_course++;

        $view_check_sql = "SELECT id FROM user_material_views WHERE user_id = ? AND material_id = ?";
        $view_check_stmt = $conn->prepare($view_check_sql);
        $view_check_stmt->bind_param("ii", $user_id, $material['id']);
        $view_check_stmt->execute();
        if ($view_check_stmt->get_result()->num_rows > 0) {
            $sections[$section_name]['viewed_materials']++;
            $total_viewed_materials_in_course++;
        }
        $sections[$section_name]['materials'][] = $material;
    }

    $course['sections'] = $sections;
    $course['total_materials_in_course'] = $total_materials_in_course;
    $course['total_viewed_materials_in_course'] = $total_viewed_materials_in_course;

    $courses_data[$course_category][] = $course;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .category-section {
            margin-bottom: 30px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .category-section h3 {
            color: #0056b3;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .course-gadgets {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .course-gadget {
            position: relative; /* For badge positioning */
            width: 280px;
            height: auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-align: center;
            font-size: 1.1em;
            font-weight: bold;
            padding: 15px;
            box-sizing: border-box;
            background: linear-gradient(to right, #4CAF50, #8BC34A);
        }
        .course-gadget.completed {
            background: linear-gradient(to right, #6c757d, #868e96);
        }
        .completed-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8em;
        }
        .course-gadget h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        .course-gadget p {
            font-size: 0.9em;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .carousel-container {
            position: relative;
            overflow: hidden;
            width: 100%;
            margin: 20px 0;
        }
        .carousel-inner {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        .carousel-item {
            min-width: 100%;
            box-sizing: border-box;
            padding: 10px;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .carousel-item h5 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
            font-size: 1.1em;
        }
        .carousel-item ul {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }
        .carousel-item ul li {
            margin-bottom: 5px;
        }
        .carousel-item ul li a {
            color: #eee;
            font-weight: normal;
            border: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }
        .carousel-item ul li a:hover {
            text-decoration: underline;
            background-color: transparent;
        }
        .carousel-nav-button {
            position: absolute;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            z-index: 10;
        }
        .carousel-nav-button.prev {
            left: 10px;
        }
        .carousel-nav-button.next {
            right: 10px;
        }
        .section-progress-bar-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 5px;
        }
        .section-progress-bar {
            height: 15px;
            background-color: #28a745;
            text-align: center;
            color: white;
            line-height: 15px;
            font-size: 0.8em;
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
                <div class="app-header">
                    <h1>Poonam Management Training and Online Exam Module</h1>
                </div>
                <h2>My Courses</h2>
            </header>
            <main>
                <?php if (empty($courses_data)): ?>
                    <p>No courses assigned yet.</p>
                <?php else: ?>
                    <?php foreach ($courses_data as $category => $courses): ?>
                        <div class="category-section">
                            <h3><?php echo htmlspecialchars($category); ?></h3>
                            <div class="course-gadgets">
                                <?php foreach ($courses as $course): ?>
                                    <?php 
                                        $gadget_class = $course['status'] == 'completed' ? 'course-gadget completed' : 'course-gadget';
                                    ?>
                                    <div class="<?php echo $gadget_class; ?>">
                                        <?php if ($course['status'] == 'completed'): ?>
                                            <div class="completed-badge">✓ Completed</div>
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($course['description']); ?></p>
                                        
                                        <?php
                                            $course_completion_percentage = ($course['total_materials_in_course'] > 0) ? 
                                                round(($course['total_viewed_materials_in_course'] / $course['total_materials_in_course']) * 100) : 0;
                                        ?>
                                        <div class="section-progress-bar-container" style="width: 90%; margin-top: 10px;">
                                            <div class="section-progress-bar" style="width: <?php echo $course_completion_percentage; ?>%;">
                                                <?php echo $course_completion_percentage; ?>% Viewed
                                            </div>
                                        </div>

                                        <div class="carousel-container">
                                            <div class="carousel-inner" id="carousel-inner-<?php echo $course['id']; ?>" data-index="0">
                                                <?php if (empty($course['sections'])): ?>
                                                    <div class="carousel-item">
                                                        <p>No sections or materials in this course.</p>
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($course['sections'] as $section_name => $section_data): ?>
                                                        <div class="carousel-item">
                                                            <h5>Section: <?php echo htmlspecialchars($section_name); ?></h5>
                                                            <ul>
                                                                <?php if (empty($section_data['materials'])): ?>
                                                                    <li>No materials in this section.</li>
                                                                <?php else: ?>
                                                                    <?php foreach ($section_data['materials'] as $material): ?>
                                                                        <li><a href="view_course_materials.php?course_id=<?php echo $course['id']; ?>&material_id=<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['file_name']); ?></a></li>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <button class="carousel-nav-button prev" onclick="moveCarousel(<?php echo $course['id']; ?>, -1)">❮</button>
                                            <button class="carousel-nav-button next" onclick="moveCarousel(<?php echo $course['id']; ?>, 1)">❯</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carousels = document.querySelectorAll('.carousel-inner');
            carousels.forEach(carousel => {
                const courseId = carousel.id.split('-').pop();
                updateCarouselButtons(courseId);
            });
        });

        function updateCarouselButtons(courseId) {
            const carouselInner = document.getElementById(`carousel-inner-${courseId}`);
            const prevButton = carouselInner.nextElementSibling;
            const nextButton = prevButton.nextElementSibling;
            const totalItems = carouselInner.children.length;
            const currentIndex = parseInt(carouselInner.dataset.index || '0');

            if (totalItems <= 1) {
                prevButton.style.display = 'none';
                nextButton.style.display = 'none';
            } else {
                prevButton.style.display = currentIndex === 0 ? 'none' : 'block';
                nextButton.style.display = currentIndex >= totalItems - 1 ? 'none' : 'block';
            }
        }

        function moveCarousel(courseId, direction) {
            const carouselInner = document.getElementById(`carousel-inner-${courseId}`);
            if (!carouselInner || carouselInner.children.length === 0) return;

            const itemWidth = carouselInner.children[0].offsetWidth;
            const totalItems = carouselInner.children.length;
            let currentIndex = parseInt(carouselInner.dataset.index || '0');

            let newIndex = currentIndex + direction;

            if (newIndex < 0) {
                newIndex = 0;
            } else if (newIndex >= totalItems) {
                newIndex = totalItems - 1;
            }

            carouselInner.style.transform = `translateX(-${newIndex * itemWidth}px)`;
            carouselInner.dataset.index = newIndex;

            updateCarouselButtons(courseId);
        }
    </script>
</body>
</html>