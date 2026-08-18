<html>
<head>
    <title>Login Fisherly</title>
    <link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" />
</head>
<body>
    <div id="firebaseui-auth-container"></div>
    <script src="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-firestore.js"></script>
    <script>
            @php
            $allParams = app('request')->request->all();
            @endphp
            // Initialize Firebase
            var config = {
                apiKey: "AIzaSyBe_jE1XvuaLT9mHySPF4dLAu3kmQXprB0",
                authDomain: "auth.fisherly.com",
                databaseURL: "https://fisherly-app.firebaseio.com",
                projectId: "fisherly-app",
                storageBucket: "fisherly-app.appspot.com",
                messagingSenderId: "854620925039"
            };
            firebase.initializeApp(config);
    
            var ui = new firebaseui.auth.AuthUI(firebase.auth());
            var uid = null;
            var uiConfig = {
                callbacks: {
                    signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                        jQuery(".box-login--signup h3").html("Logging In<span class='loader__dot'>.</span><span class='loader__dot'>.</span><span class='loader__dot'>.</span>");
                        firebase.auth().currentUser.getIdToken(/* forceRefresh */ true).then(function(idToken) {
                            console.log(idToken);
                            document.location = '{{route('handleAuth')}}'+"?token="+idToken+"&agent=@if(isset($agentId)){{$agentId}}@endif&f=@if(count($allParams) > 0)&{!!http_build_query($allParams)!!}@endif";
                        }).catch(function(error) {
                            // Handle error
                        });
                        return false;
                    },
                    uiShown: function() {
                        document.getElementById('loader').style.display = 'none';
                    }
                },
                signInFlow: 'redirect',
                signInSuccessUrl: '{{route('handleAuth')}}',
                credentialHelper: firebaseui.auth.CredentialHelper.NONE,
                signInOptions: [
                    firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                    firebase.auth.EmailAuthProvider.PROVIDER_ID,
                    firebase.auth.FacebookAuthProvider.PROVIDER_ID,
                    //firebase.auth.PhoneAuthProvider.PROVIDER_ID
                ],
                // Terms of service url.
                tosUrl: '/terms-and-conditions',
                // Privacy policy url.
                privacyPolicyUrl: '/privacy-policy'
            };
    
    
            ui.start('#firebaseui-auth-container', uiConfig);
        
        </script>
</body>
</html>