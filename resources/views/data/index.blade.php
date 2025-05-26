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
<div class="flex justify-end w-full mb-4 ">
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
$(document).ready(function () {
    $('#levelSelect').change(function () {
        const levelId = $(this).val();
        $('#courseSelect').prop('disabled', true).html('<option value="">Chargement...</option>');
        $('#lessonSelect').prop('disabled', true).html('<option value="">-- Choisir une leçon --</option>');

        if (levelId) {
            $.ajax({
                url: '/get-courses/' + levelId,
                type: 'GET',
                success: function (courses) {
                    let options = '<option value="">-- Choisir un cours --</option>';
                    $.each(courses, function (key, course) {
                        options += `<option value="${course.id}">${course.name}</option>`;
                    });
                    $('#courseSelect').html(options).prop('disabled', false);
                }
            });
        }
    });
    
$('#courseSelect').change(function () {
    const courseId = $(this).val();
    $('#lessonSelect').prop('disabled', true).html('<option value="">Chargement...</option>');
    $('#customContentTable').html(''); // Clear previous data

    if (courseId) {
        $.ajax({
            url: '/get-lessons/' + courseId,
            type: 'GET',
            success: function (response) {
                if (response.type === 'lessons') {
                    let options = '<option value="">-- Choisir une leçon --</option>';
                    $.each(response.items, function (key, lesson) {
                        options += `<option value="${lesson.id}">${lesson.title}</option>`;
                    });
                    $('#lessonSelect').html(options).prop('disabled', false);
                } else if (response.type === 'data') {
                    $('#lessonSelect').html('<option value="">Aucune leçon disponible</option>').prop('disabled', true);
                    renderContentTable(response.items);
                }
            },
            error: function () {
                $('#lessonSelect').html('<option value="">Erreur lors du chargement</option>');
            }
        });
    }
});


    $('#lessonSelect').change(function () {
        const lessonId = $(this).val();
        if (lessonId) {
            $.ajax({
                url: '/get-data/' + lessonId,
                type: 'GET',
                success: function (dataItems) {
                    renderContentTable(dataItems);
                },
                error: function () {
                    $('#customContentTable').html(
                        '<tr><td colspan="3" class="text-center text-red-600 py-4">Erreur lors du chargement</td></tr>'
                    );
                }
            });
        } else {
            $('#customContentTable').html('<tr><td colspan="3" class="text-center py-4">Aucune donnée disponible</td></tr>');
        }
        });
});
// Function to open PDF in modal
function openPdfModal(url) {
    $('#pdfViewer').attr('src', url);
    $('#pdfModal').removeClass('hidden').addClass('flex');
}

function closePdfModal() {
    $('#pdfModal').addClass('hidden').removeClass('flex');
    $('#pdfViewer').attr('src', ''); // clear PDF
}

//custom function to render content table
function renderContentTable(dataItems) {
    const grouped = {};

    // Group by content type
    dataItems.forEach(item => {
        if (!grouped[item.value]) {
            grouped[item.value] = [];
        }
        grouped[item.value].push({
            title: item.title,
            url: item.url
        });
    });

    let html = '';
    for (const value in grouped) {
        html += `
            <tr class="bg-gray-100 font-semibold"><td colspan="3">${value}</td></tr>
        `;
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

    $('#customContentTable').html(
        html || '<tr><td colspan="3" class="text-center py-4">Aucune donnée disponible</td></tr>'
    );
}
</script>

</body>
</html>
