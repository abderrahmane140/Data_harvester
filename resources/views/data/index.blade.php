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
            <select id="typeSelect" class="w-full border border-gray-300 rounded-md px-3 py-2" disabled>
                <option value="">-- Tous les types --</option>
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
    // Type equivalents mapping (Arabic : [French equivalents])
const typeEquivalents = {
    'دروس': ['Coure', 'cours'],
    'فروض': ['exam', 'examen', 'devoir'],  // This will now be handled specially
    'تمارين': ['exercice', 'exercise'],
    'ملخصات': ['résumé', 'resume', 'summary'],
    'فيديو': ['video', 'vidéo'],
    'الامتحان': ['examen final', 'final exam']
};

    // Function to get Arabic equivalent of a type
    function getArabicType(type) {
        for (const [arabic, frenchTypes] of Object.entries(typeEquivalents)) {
            if (arabic === type || frenchTypes.includes(type)) {
                return arabic;
            }
        }
        return type;
    }

    // Function to get all equivalent types
    function getEquivalentTypes(type) {
        if (type === 'الامتحان') {
            return ['الامتحان'];
        }
        
        for (const [arabic, frenchTypes] of Object.entries(typeEquivalents)) {
            if (arabic === type || frenchTypes.includes(type)) {
                return [arabic, ...frenchTypes];
            }
        }
        return [type];
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
            currentCourseId = '';
            currentLessonId = '';
            currentType = '';
            
            $('#courseSelect').prop('disabled', !currentLevelId)
                            .html(currentLevelId ? '<option value="">Chargement...</option>' : '<option value="">-- Choisir un cours --</option>');
            $('#lessonSelect').prop('disabled', true)
                            .html('<option value="">-- Choisir une leçon --</option>');
            $('#typeSelect').prop('disabled', true)
                          .html('<option value="">-- Tous les types --</option><option value="الامتحان">الامتحان</option>');
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
            currentLessonId = '';
            currentType = '';
            
            $('#lessonSelect').prop('disabled', !currentCourseId)
                            .html(currentCourseId ? '<option value="">Chargement...</option>' : '<option value="">-- Choisir une leçon --</option>');
            $('#typeSelect').prop('disabled', true)
                          .html('<option value="">-- Tous les types --</option><option value="الامتحان">الامتحان</option>');
            
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
                            updateTypeSelect(response.items);
                            $('#typeSelect').prop('disabled', false);
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
            currentType = '';
            
            if (currentLessonId) {
                $('#typeSelect').prop('disabled', false)
                              .html('<option value="">-- Tous les types --</option><option value="الامتحان">الامتحان</option>');
                refreshTableData();
            } else {
                $('#typeSelect').prop('disabled', true)
                              .html('<option value="">-- Tous les types --</option><option value="الامتحان">الامتحان</option>');
                $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4">Sélectionnez une leçon</td></tr>');
            }
        });

        // Type select change handler
        $('#typeSelect').change(function() {
            currentType = $(this).val();
            refreshTableData();
        });

        // Function to update type select dropdown
        // Modify the updateTypeSelect function to NOT filter the dropdown options
function updateTypeSelect(dataItems, preserveAllTypes = true) {
    const $typeSelect = $('#typeSelect');
    const currentValue = $typeSelect.val();
    
    // Only update the dropdown if we're not preserving all types
    // or if it's the initial load (no currentValue)
    if (!preserveAllTypes || !currentValue) {
        // Get all unique Arabic types from the data
        const arabicTypes = new Set();
        dataItems.forEach(item => {
            arabicTypes.add(getArabicType(item.value));
        });
        
        let options = '<option value="">-- Tous les types --</option><option value="الامتحان">الامتحان</option>';
        
        // Add other types sorted
        Array.from(arabicTypes).sort().forEach(type => {
            if (type !== 'الامتحان') {
                options += `<option value="${type}">${type}</option>`;
            }
        });
        
        $typeSelect.html(options).prop('disabled', false);
        
        // Restore selection if possible
        if (currentValue) {
            $typeSelect.val(currentValue);
        }
    }
}

// Modify the refreshTableData function calls to preserve all types
function refreshTableData() {
    // Special case for exams
     if (currentType === 'الامتحان' || currentType === 'فروض') {
        if (!currentLevelId || !currentCourseId) {
            $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4">Sélectionnez un niveau et un cours</td></tr>');
            return;
        }
        
        $.ajax({
            url: '/get-special-data',
            type: 'GET',
            data: {
                level_id: currentLevelId,
                course_id: currentCourseId,
                type: currentType  // Send which special type we're requesting
            },
            success: function(dataItems) {
                // Normalize all as the selected type
                const normalizedItems = dataItems.map(item => ({
                    ...item,
                    displayValue: currentType
                }));
                renderContentTable(normalizedItems);
            },
            error: function() {
                $('#customContentTable').html(
                    '<tr><td colspan="3" class="text-center py-4 text-red-600">Erreur de chargement</td></tr>'
                );
            }
        });
        return;
    }

    // Normal case for other types
    if (currentLessonId) {
        $.ajax({
            url: '/get-data/' + currentLessonId + '/' + currentLevelId + '/' + currentCourseId,
            type: 'GET',
            data: { type: currentType },
            success: function(dataItems) {
                const normalizedItems = dataItems.map(item => ({
                    ...item,
                    displayValue: getArabicType(item.value)
                }));
                // Pass true to preserve all types in dropdown
                updateTypeSelect(dataItems, true);
                renderContentTable(normalizedItems);
            },
            error: function() {
                $('#customContentTable').html(
                    '<tr><td colspan="3" class="text-center py-4 text-red-600">Erreur de chargement des données</td></tr>'
                );
            }
        });
    } else if (currentCourseId) {
        // Course-level data
        $.ajax({
            url: '/get-lessons/' + currentCourseId,
            type: 'GET',
            success: function(response) {
                if (response.type === 'data') {
                    const normalizedItems = response.items.map(item => ({
                        ...item,
                        displayValue: getArabicType(item.value)
                    }));
                    
                    // Filter by type if selected
                    const filteredItems = currentType 
                        ? normalizedItems.filter(item => {
                            const equivalents = getEquivalentTypes(currentType);
                            return equivalents.includes(item.value) || 
                                   equivalents.includes(item.displayValue);
                        })
                        : normalizedItems;
                    
                    // Pass true to preserve all types in dropdown
                    updateTypeSelect(response.items, true);
                    renderContentTable(filteredItems);
                }
            },
            error: function() {
                $('#customContentTable').html(
                    '<tr><td colspan="3" class="text-center py-4 text-red-600">Erreur de chargement des données</td></tr>'
                );
            }
        });
    } else {
        $('#customContentTable').html(
            '<tr><td colspan="3" class="text-center py-4">Sélectionnez un cours ou une leçon</td></tr>'
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
            
            // Group by display value
            dataItems.forEach(item => {
                const displayValue = item.displayValue || getArabicType(item.value);
                if (!grouped[displayValue]) {
                    grouped[displayValue] = [];
                }
                grouped[displayValue].push({
                    title: item.title,
                    url: item.url,
                    type: item.value
                });
            });

            let html = '';
            for (const [type, items] of Object.entries(grouped)) {
                if (!currentType) {
                    html += `<tr class="bg-gray-100 font-semibold"><td colspan="3">${type}</td></tr>`;
                }
                
                items.forEach(item => {
                    const isVideo = type === 'فيديو' || 
                                  (typeEquivalents['فيديو'] && typeEquivalents['فيديو'].includes(item.type));
                    
                    html += `
                        <tr>
                            <td class="border px-4 py-2">${type}</td>
                            <td class="border px-4 py-2">${item.title}</td>
                            <td class="border px-4 py-2 flex justify-center gap-2">
                                ${isVideo ? `
                                <a href="${item.url}" target="_blank" title="Voir la vidéo" class="text-blue-600 hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                ` : `
                                <a href="${item.url}" download title="Télécharger" class="text-green-600 hover:text-green-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                                    </svg>
                                </a>
                                <button onclick="openPdfModal('${item.url}')" title="Voir" class="text-blue-600 hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                `}
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