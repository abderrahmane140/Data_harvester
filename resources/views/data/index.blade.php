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
                <select id="levelSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                    <option value="">-- Choisir un niveau --</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-1/3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cours :</label>
                <select id="courseSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" disabled>
                    <option value="">-- Choisir un cours --</option>
                </select>
            </div>

            <div class="w-full md:w-1/3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Leçon :</label>
                <select id="lessonSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" disabled>
                    <option value="">-- Choisir une leçon --</option>
                </select>
            </div>
        </div>

        <h2 class="text-xl font-semibold text-gray-800 mb-4">Contenu du cours</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300 rounded-md overflow-hidden text-sm">
                <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left border">Type</th>
                        <th class="px-4 py-2 text-left border">Titre</th>
                        <th class="px-4 py-2 text-left border">URL</th>
                    </tr>
                </thead>
                <tbody id="contentTable" class="text-gray-700">
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
            $('#contentTable').empty();

            if (levelId) {
                $.get('/courses/' + levelId, function (courses) {
                    $('#courseSelect').html('<option value="">-- Choisir un cours --</option>');
                    courses.forEach(course => {
                        $('#courseSelect').append(`<option value="${course.id}">${course.name}</option>`);
                    });
                    $('#courseSelect').prop('disabled', false);
                });
            } else {
                $('#courseSelect').html('<option value="">-- Choisir un cours --</option>').prop('disabled', true);
            }
        });

        $('#courseSelect').on('change', function () {
            let courseId = $(this).val();
            $('#contentTable').empty();
            $('#lessonSelect').html('<option>Chargement...</option>').prop('disabled', true);

            if (courseId) {
                $.get('/details/' + courseId, function (data) {
                    // Remplir tableau
                    data.lessons.forEach(lesson => {
                        $('#contentTable').append(`
                            <tr>
                                <td class="border px-4 py-2">Leçon</td>
                                <td class="border px-4 py-2">${lesson.title}</td>
                                <td class="border px-4 py-2">
                                    <a href="${lesson.url}" target="_blank" class="text-blue-500 underline">${lesson.url ?? ''}</a>
                                </td>
                            </tr>
                        `);
                    });

                    data.exercises.forEach(exercise => {
                        $('#contentTable').append(`
                            <tr>
                                <td class="border px-4 py-2">Exercice</td>
                                <td class="border px-4 py-2">${exercise.title}</td>
                                <td class="border px-4 py-2">
                                    <a href="${exercise.url}" target="_blank" class="text-blue-500 underline">${exercise.url ?? ''}</a>
                                </td>
                            </tr>
                        `);
                    });

                    // Remplir select leçons
                    $('#lessonSelect').html('<option value="">-- Choisir une leçon --</option>');
                    data.lessons.forEach(lesson => {
                        $('#lessonSelect').append(`<option value="${lesson.id}">${lesson.title}</option>`);
                    });
                    $('#lessonSelect').prop('disabled', false);
                });
            } else {
                $('#lessonSelect').html('<option value="">-- Choisir une leçon --</option>').prop('disabled', true);
            }
        });

        // Optionnel: Gestion sélection leçon (exemple d’affichage ou action)
        $('#lessonSelect').on('change', function() {
            let lessonId = $(this).val();
            if (lessonId) {
                alert("Leçon sélectionnée : " + lessonId);
                // Tu peux ici faire d’autres requêtes AJAX pour afficher plus d’infos par exemple
            }
        });
    </script>

</body>
</html>
