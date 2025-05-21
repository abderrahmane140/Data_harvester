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
                    <th class="px-4 py-2 border">دروس</th>
                    <th class="px-4 py-2 border">ملخصات</th>
                    <th class="px-4 py-2 border">تمارين</th>
                    <th class="px-4 py-2 border">فروض</th>
                    <th class="px-4 py-2 border">جذاذات</th>
                    <th class="px-4 py-2 border">فيديوهات</th>
                </tr>
            </thead>
            <tbody id="customContentTable" class="text-gray-700">
                <!-- Données injectées ici -->
            </tbody>
        </table>
    </div>
</div>

<script>
    $('#levelSelect').on('change', function () {
        let levelId = $(this).val();
        $('#courseSelect').html('<option>Chargement...</option>').prop('disabled', true);
        $('#lessonSelect').html('<option>-- Choisir une leçon --</option>').prop('disabled', true);
        $('#customContentTable').empty();

        if (levelId) {
            $.get('/courses/' + levelId, function (courses) {
                $('#courseSelect').html('<option value="">-- Choisir un cours --</option>');
                courses.forEach(course => {
                    $('#courseSelect').append(`<option value="${course.id}">${course.name}</option>`);
                });
                $('#courseSelect').prop('disabled', false);
            });
        }
    });

    $('#courseSelect').on('change', function () {
        let courseId = $(this).val();
        $('#customContentTable').empty();
        $('#lessonSelect').html('<option>Chargement...</option>').prop('disabled', true);

        if (courseId) {
            $.get('/details/' + courseId, function (data) {
                let rowCount = Math.max(data.lessons.length, data.exercises.length);

                for (let i = 0; i < rowCount; i++) {
                    let lesson = data.lessons[i] || {};
                    let exercise = data.exercises[i] || {};

                    $('#customContentTable').append(`
                        <tr>
                            <td class="border px-4 py-2">
                                ${lesson.title ? `<a href="${lesson.url}" target="_blank" class="text-blue-600 underline">${lesson.title}</a>` : ''}
                            </td>
                            <td class="border px-4 py-2">—</td>
                            <td class="border px-4 py-2">
                                ${exercise.title ? `<a href="${exercise.url}" target="_blank" class="text-green-600 underline">${exercise.title}</a>` : ''}
                            </td>
                            <td class="border px-4 py-2">—</td>
                            <td class="border px-4 py-2">—</td>
                            <td class="border px-4 py-2">—</td>
                        </tr>
                    `);
                }

                // Remplir le select leçon
                $('#lessonSelect').html('<option value="">-- Choisir une leçon --</option>');
                data.lessons.forEach(lesson => {
                    $('#lessonSelect').append(`<option value="${lesson.id}">${lesson.title}</option>`);
                });
                $('#lessonSelect').prop('disabled', false);
            });
        }
    });

    $('#lessonSelect').on('change', function () {
        let lessonId = $(this).val();
        if (lessonId) {
            alert("Leçon sélectionnée : " + lessonId);
        }
    });
</script>

</body>
</html>
