<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello World - TallCMS Plugin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
    <div class="max-w-lg mx-auto p-8">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">Hello, World!</h1>

            <p class="text-gray-600 mb-6">
                This page is served by the <strong class="text-indigo-600">Hello World</strong> plugin.
            </p>

            <div class="bg-gray-50 rounded-lg p-4 text-left text-sm">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-500">Plugin:</span>
                    <span class="font-medium text-gray-900">tallcms/hello-world</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-500">Version:</span>
                    <span class="font-medium text-gray-900">1.0.0</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Route:</span>
                    <span class="font-medium text-gray-900">/hello</span>
                </div>
            </div>

            <a href="/" class="inline-flex items-center mt-6 text-indigo-600 hover:text-indigo-800 font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Home
            </a>
        </div>

        <p class="text-center text-gray-500 text-sm mt-6">
            Powered by TallCMS Plugin System
        </p>
    </div>
</body>
</html>
