<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Affichage des Données</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 p-6 font-sans">
<div class="flex justify-end w-full mb-4">
    <a class="p-1 rounded-md w-16 text-center bg-sky-400 hover:bg-sky-500" href="{{route('home')}}">Home</a>
</div>  
<div class="max-w-5xl mx-auto bg-white shadow-lg rounded-xl p-6">
    <h1 class="text-2xl font-bold mb-4 text-gray-800">Sélection de niveau, cours et leçon</h1>

    <div class="flex flex-col md:flex-row items-center gap-4 mb-6">
        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Niveau :</label>
            <select id="levelSelect" class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">-- Choisir un niveau --</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Cours :</label>
            <select id="courseSelect" class="w-full border border-gray-300 rounded-md px-3 py-2" disabled>
                <option value="">-- Choisir un cours --</option>
            </select>
        </div>

        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Leçon :</label>
            <select id="lessonSelect" class="w-full border border-gray-300 rounded-md px-3 py-2" disabled>
                <option value="">-- Choisir une leçon --</option>
            </select>
        </div>

        <div class="w-full md:w-1/3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Type :</label>
            <select id="typeSelect" class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">-- Tous les types --</option>
                <option value="دروس">دروس / Cours</option>
                <option value="فروض">فروض / Examens</option>
                <option value="تمارين">تمارين / Exercices</option>
                <option value="ملخصات">ملخصات / Résumés</option>
                <option value="فيديو">فيديو / Vidéos</option>
                <option value="الامتحان">الامتحان</option>
            </select>
        </div>
    </div>

    <h2 class="text-xl font-semibold text-gray-800 mb-4">محتوى الدورة</h2>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-300 rounded-md overflow-hidden text-sm text-center">
            <thead class="bg-gray-200 text-gray-700">
                <tr>
                    <th class="px-4 py-2 border">نوع المحتوى</th>
                    <th class="px-4 py-2 border">العنوان</th>
                    <th class="px-4 py-2 border">رابط التحميل</th>
                </tr>
            </thead>
            <tbody id="customContentTable" class="text-gray-700">
                <tr>
                    <td colspan="3" class="text-center py-4">Sélectionnez un niveau, cours et leçon</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="pdfModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 items-center justify-center">
  <div class="bg-white w-11/12 md:w-3/4 h-[90vh] rounded-lg shadow-lg flex flex-col">
    <div class="flex justify-between items-center p-4 border-b">
      <h2 class="text-lg font-semibold">Aperçu PDF</h2>
      <button onclick="closePdfModal()" class="text-red-600 font-bold text-xl">&times;</button>
    </div>
    <div class="flex-grow">
      <iframe id="pdfViewer" class="w-full h-full" frameborder="0"></iframe>
    </div>
  </div>
</div>

<script>
    // Add this at the top of your script
    const typeEquivalents = {
        // Arabic : [French equivalents]
        'دروس': ['Coure', 'cours'],
        'فروض': ['exam', 'examen', 'devoir'],
        'تمارين': ['exercice', 'exercise'],
        'ملخصات': ['résumé', 'resume', 'summary'],
        'فيديو': ['video', 'vidéo']
    };

    // Function to get all equivalent types
    function getEquivalentTypes(type) {
        for (const [arabic, frenchTypes] of Object.entries(typeEquivalents)) {
            if (arabic === type || frenchTypes.includes(type)) {
                return [arabic, ...frenchTypes];
            }
        }
        return [type]; // Return the type itself if no equivalents found
    }
$(document).ready(function () {
    // Initialize variables
    let currentLevelId = '';
    let currentCourseId = '';
    let currentLessonId = '';
    let currentType = '';

    // Level select change handler
    $('#levelSelect').change(function () {
        currentLevelId = $(this).val();
        $('#courseSelect').prop('disabled', !currentLevelId)
                          .html(currentLevelId ? '<option value="">Chargement...</option>' : '<option value="">-- Choisir un cours --</option>');
        $('#lessonSelect').prop('disabled', true)
                          .html('<option value="">-- Choisir une leçon --</option>');
        $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4">Sélectionnez un cours</td></tr>');

        if (currentLevelId) {
            $.ajax({
                url: '/get-courses/' + currentLevelId,
                type: 'GET',
                success: function (courses) {
                    let options = '<option value="">-- Choisir un cours --</option>';
                    $.each(courses, function (key, course) {
                        options += `<option value="${course.id}">${course.name}</option>`;
                    });
                    $('#courseSelect').html(options).prop('disabled', false);
                },
                error: function() {
                    $('#courseSelect').html('<option value="">Erreur de chargement</option>');
                }
            });
        }
    });
    
    // Course select change handler
    $('#courseSelect').change(function () {
        currentCourseId = $(this).val();
        $('#lessonSelect').prop('disabled', !currentCourseId)
                          .html(currentCourseId ? '<option value="">Chargement...</option>' : '<option value="">-- Choisir une leçon --</option>');
        
        if (currentCourseId) {
            $.ajax({
                url: '/get-lessons/' + currentCourseId,
                type: 'GET',
                success: function (response) {
                    if (response.type === 'lessons') {
                        let options = '<option value="">-- Choisir une leçon --</option>';
                        $.each(response.items, function (key, lesson) {
                            options += `<option value="${lesson.id}">${lesson.title}</option>`;
                        });
                        $('#lessonSelect').html(options).prop('disabled', false);
                        $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4">Sélectionnez une leçon</td></tr>');
                    } else if (response.type === 'data') {
                        $('#lessonSelect').html('<option value="">Aucune leçon disponible</option>').prop('disabled', true);
                        renderContentTable(response.items);
                    }
                },
                error: function() {
                    $('#lessonSelect').html('<option value="">Erreur de chargement</option>');
                    $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4 text-red-600">Erreur lors du chargement</td></tr>');
                }
            });
        } else {
            $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4">Sélectionnez un cours</td></tr>');
        }
    });

    // Lesson select change handler
    $('#lessonSelect').change(function () {
        currentLessonId = $(this).val();
        refreshTableData();
    });

    // Type select change handler
    $('#typeSelect').change(function() {
        currentType = $(this).val();
        refreshTableData();
    });

    // Function to refresh table data based on current selections
function refreshTableData() {
    // Special case for "الامتحان" - fetch exams without lesson_id
    if (currentType === 'الامتحان') {
        $.ajax({
            url: '/get-data/0', // Using 0 as dummy lesson_id
            type: 'GET',
            data: { type: 'الامتحان' },
            success: function(dataItems) {
                // Normalize display (though exams will already be in Arabic)
                const normalizedItems = dataItems.map(item => ({
                    ...item,
                    displayValue: 'الامتحان'
                }));
                renderContentTable(normalizedItems);
            },
            error: function() {
                $('#customContentTable').html(
                    '<tr><td colspan="3" class="text-center py-4 text-red-600">Error loading exams</td></tr>'
                );
            }
        });
        return;
    }

    // Normal case for other types
    if (currentLessonId) {
        // Load lesson-specific data with type filter
        $.ajax({
            url: '/get-data/' + currentLessonId,
            type: 'GET',
            data: { type: currentType },
            success: function(dataItems) {
                // Normalize all types to Arabic for display
                const normalizedItems = dataItems.map(item => {
                    const arabicType = Object.entries(typeEquivalents).find(
                        ([arabic, frenchTypes]) => frenchTypes.includes(item.value)
                    )?.[0] || item.value;
                    return {...item, displayValue: arabicType};
                });
                renderContentTable(normalizedItems);
            },
            error: function() {
                $('#customContentTable').html(
                    '<tr><td colspan="3" class="text-center py-4 text-red-600">Error loading lesson data</td></tr>'
                );
            }
        });
    } else if (currentCourseId) {
        // Load course-level data
        $.ajax({
            url: '/get-lessons/' + currentCourseId,
            type: 'GET',
            success: function(response) {
                if (response.type === 'data') {
                    // Normalize and filter items
                    const filteredItems = response.items.map(item => {
                        const arabicType = Object.entries(typeEquivalents).find(
                            ([arabic, frenchTypes]) => frenchTypes.includes(item.value)
                        )?.[0] || item.value;
                        return {...item, displayValue: arabicType};
                    }).filter(item => {
                        if (!currentType) return true;
                        const equivalentTypes = getEquivalentTypes(currentType);
                        return equivalentTypes.includes(item.value) || 
                               equivalentTypes.includes(item.displayValue);
                    });
                    renderContentTable(filteredItems);
                }
            },
            error: function() {
                $('#customContentTable').html(
                    '<tr><td colspan="3" class="text-center py-4 text-red-600">Error loading course data</td></tr>'
                );
            }
        });
    } else {
        // No lesson or course selected
        $('#customContentTable').html(
            '<tr><td colspan="3" class="text-center py-4">Please select a course or lesson</td></tr>'
        );
    }
}
    // Function to render content table
function renderContentTable(dataItems) {
    if (!dataItems || dataItems.length === 0) {
        $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4">Aucune donnée disponible</td></tr>');
        return;
    }

    const grouped = {};
    
    // Group by display value (Arabic normalized)
    dataItems.forEach(item => {
        const displayValue = item.displayValue || item.value;
        if (!grouped[displayValue]) {
            grouped[displayValue] = [];
        }
        grouped[displayValue].push({
            title: item.title,
            url: item.url
        });
    });

    let html = '';
    for (const value in grouped) {
        if (!currentType) {
            html += `<tr class="bg-gray-100 font-semibold"><td colspan="3">${value}</td></tr>`;
        }
        
        grouped[value].forEach(item => {
            html += `
                <tr>
                    <td class="border px-4 py-2">${value}</td>
                    <td class="border px-4 py-2">${item.title}</td>
                    <td class="border px-4 py-2 flex justify-center gap-2">
                  <a href="${item.url}" download title="Télécharger" class="text-green-600 hover:text-green-800">
                            <!-- Download Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                            </svg>
                        </a>
                        <button onclick="openPdfModal('${item.url}')" title="Voir"
                                class="text-blue-600 hover:text-blue-800">
                            <!-- Eye Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    $('#customContentTable').html(html);
}
});

// Modal functions
function openPdfModal(url) {
    $('#pdfViewer').attr('src', url);
    $('#pdfModal').removeClass('hidden').addClass('flex');
}

function closePdfModal() {
    $('#pdfModal').addClass('hidden').removeClass('flex');
    $('#pdfViewer').attr('src', '');
}
</script>
</body>
</html>