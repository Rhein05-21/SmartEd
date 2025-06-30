<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEd - SPA Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg,rgb(176, 230, 188) 0%,rgb(198, 251, 187) 100%);
        }
        .container-auth {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
            position: relative;
            overflow: hidden;
            width: 800px;
            max-width: 100%;
            min-height: 500px;
        }
        .forms-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 50%;
            height: 100%;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .form {
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            width: 100%;
            max-width: 320px;
            padding: 0 30px;
            position: absolute;
            left: 0;
            right: 0;
            margin: auto;
            opacity: 0;
            z-index: 1;
            transition: opacity 0.3s, z-index 0.3s;
        }
        .form.active {
            opacity: 1;
            z-index: 2;
            position: relative;
        }
        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            z-index: 100;
            transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .overlay {
            background: linear-gradient(135deg, #008037 0%, #00b050 100%);
            color: #fff;
            position: absolute;
            left: -100%;
            width: 200%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            transform: translateX(0);
        }
        .overlay-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 50%;
            height: 100%;
            position: absolute;
            top: 0;
        }
        .overlay-left {
            left: 0;
        }
        .overlay-right {
            right: 0;
        }
        .container-auth.right-panel-active .forms-container {
            transform: translateX(100%);
        }
        .container-auth.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }
        .container-auth.right-panel-active .overlay {
            transform: translateX(50%);
        }
        .container-auth .sign-up-form {
            pointer-events: none;
        }
        .container-auth.right-panel-active .sign-up-form {
            pointer-events: auto;
        }
        .container-auth .sign-in-form {
            pointer-events: auto;
        }
        .container-auth.right-panel-active .sign-in-form {
            pointer-events: none;
        }
        /* Main action buttons: white background, green text, green on hover */
        .bg-gradient-to-r.from-purple-500.to-blue-500 {
            background: #fff !important;
            color: #008037 !important;
            border: 2px solid #008037 !important;
            transition: background 0.3s, color 0.3s;
        }
        .bg-gradient-to-r.from-purple-500.to-blue-500:hover,
        .hover\:from-purple-600:hover, .hover\:to-blue-600:hover {
            background: #008037 !important;
            color: #fff !important;
        }
        /* Green border for overlay buttons */
        .border-white {
            border-color: #008037 !important;
        }
        .hover\:bg-white:hover {
            background: #008037 !important;
            color: #fff !important;
        }
        /* Input focus border green */
        .focus\:border-purple-400:focus {
            border-color: #008037 !important;
        }
        /* Text color adjustments for green theme */
        .text-purple-700 {
            color: #008037 !important;
        }
        .text-purple-500 {
            color: #008037 !important;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="container-auth" id="container">
        <div class="forms-container">
            <form class="form sign-in-form active">
                <h2 class="text-3xl font-bold mb-2 text-purple-700">Welcome back!</h2>
                <div class="w-full space-y-6 mt-6">
                    <input type="text" placeholder="Student Number" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <input type="password" placeholder="Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-full font-semibold text-lg hover:from-purple-600 hover:to-blue-600 transition">Log in</button>
                </div>
                <button type="button" id="show-forgot" class="mt-4 text-sm text-purple-500 hover:underline">Forgot your password?</button>
            </form>
            <form class="form sign-up-form">
                <h2 class="text-3xl font-bold mb-2 text-purple-700">Create account</h2>
                <div class="w-full space-y-6 mt-6">
                    <input type="email" placeholder="Email address" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <input type="password" placeholder="Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <input type="password" placeholder="Confirm Password" class="w-full px-0 py-2 border-0 border-b-2 border-gray-200 focus:border-purple-400 bg-transparent text-lg focus:outline-none" required>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white py-3 rounded-full font-semibold text-lg hover:from-purple-600 hover:to-blue-600 transition">SIGN UP</button>
                </div>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h2 class="text-3xl font-bold mb-2">Already have an account ?</h2>
                    <p class="mb-6">Login with your email & password</p>
                    <button class="border border-white px-8 py-2 rounded-full font-semibold hover:bg-white hover:text-blue-700 transition text-lg" id="signIn">Log in</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h2 class="text-3xl font-bold mb-2">Don't have an account ?</h2>
                    <p class="mb-6">Register here as a Student
                    </p>
                    <button class="border border-white px-8 py-2 rounded-full font-semibold hover:bg-white hover:text-blue-700 transition text-lg" id="signUp">SIGN UP</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const container = document.getElementById('container');
        const signUpBtn = document.getElementById('signUp');
        const signInBtn = document.getElementById('signIn');
        const signInForm = document.querySelector('.sign-in-form');
        const signUpForm = document.querySelector('.sign-up-form');

        signUpBtn.onclick = () => {
            container.classList.add('right-panel-active');
            signInForm.classList.remove('active');
            signUpForm.classList.add('active');
        };
        signInBtn.onclick = () => {
            container.classList.remove('right-panel-active');
            signUpForm.classList.remove('active');
            signInForm.classList.add('active');
        };
        document.getElementById('show-forgot').onclick = () => {
            alert('Forgot password functionality goes here.');
        };
    </script>
</body>
</html> 