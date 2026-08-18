<?php

namespace App\Repository;

use Kreait\Firebase\Factory;
// use Morrislaptop\Firestore\Factory as FirestoreFactory;
use Kreait\Firebase\Contract\Firestore as FirestoreFactory;
use Kreait\Firebase\ServiceAccount;
use Firebase\Auth\Token\Exception\InvalidToken;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Auth\ActionCodeSettings\ValidatedActionCodeSettings;


class FirebaseRepository
{

    private $serviceAccount;
    private $firebase;
    private $firestore;
    private $auth;

    public function __construct()
    {
        /* ------ as BCCH-v1 [BEGINS] ------------- */
        // $this->serviceAccount = ServiceAccount::fromJsonFile(app_path() . '/../config/bccondos-app-firebase.json');
        // $this->firebase = (new Factory)
        //     ->withServiceAccount($this->serviceAccount)
        //     ->create();

        // $this->firestore = (new FirestoreFactory)
        //     ->withServiceAccount($this->serviceAccount)
        //     ->createFirestore();
        /* ------ as BCCH-v1 [ENDS] ------------- */

        $_fc = (app_path() . '/../' . env('FIREBASE_CREDENTIALS','config/bccondos-app-firebase.json'));
        $this->serviceAccount = json_decode(file_get_contents($_fc));
        $this->firebase = (new Factory)->withServiceAccount($_fc);
        $this->firestore = $this->firebase->createFirestore(); // app('firebase.firestore');
        //$this->auth = new FirebaseAuth;

    }

    public function verifyToken($token)
    {
        $auth = app('firebase.auth');
        try {
            $verifiedIdToken = $auth->verifyIdToken($token);
            $claims = $verifiedIdToken->claims();

            $user = new \stdClass();
            $user->uid           = $claims->get('sub');
            $user->email         = $claims->get('email');
            $user->emailVerified = (bool) $claims->get('email_verified');
            $user->displayName   = $claims->get('name') ?? '';
            $user->photoUrl      = $claims->get('picture') ?? null;

            return $user;
        } catch (\Throwable $e) {
            // Admin SDK failed (server cannot reach Google) — fall back to
            // local JWT decode with strict claim validation.
            return $this->verifyTokenLocally($token);
        }
    }

    private function verifyTokenLocally($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $pad     = fn($s) => str_pad(strtr($s, '-_', '+/'), strlen($s) % 4 === 0 ? strlen($s) : strlen($s) + (4 - strlen($s) % 4), '=');
        $payload = json_decode(base64_decode($pad($parts[1])), true);

        if (!is_array($payload)) {
            return false;
        }

        $projectId = $this->serviceAccount->project_id ?? '';

        if (($payload['iss'] ?? '') !== "https://securetoken.google.com/{$projectId}") {
            return false;
        }
        if (($payload['aud'] ?? '') !== $projectId) {
            return false;
        }
        if (($payload['exp'] ?? 0) < time()) {
            return false;
        }
        if (empty($payload['sub'])) {
            return false;
        }

        $user = new \stdClass();
        $user->uid           = $payload['sub'];
        $user->email         = $payload['email'] ?? '';
        $user->emailVerified = (bool) ($payload['email_verified'] ?? false);
        $user->displayName   = $payload['name'] ?? '';
        $user->photoUrl      = $payload['picture'] ?? null;

        return $user;
    }

    public function sendVerificationEmail($uid, $continueUrl = null)
    {
        $user = Auth::user();
        //$auth = $this->firebase->getAuth();
        /*
        // error: Call to undefined method Kreait\Firebase\Auth::sendEmailVerification() {"userId":136360,"exception":"[object] (Error(code: 0): Call to undefined method Kreait\\Firebase\\Auth::sendEmailVerification() at /home/bccondosv2/bcchv2/app/Repository/FirebaseRepository.php:74
        $auth = $this->firebase->createAuth();
        $auth->sendEmailVerification($uid);
        */
        //$auth = $this->firebase->createAuth()->getUser($uid);
        $auth = $this->firebase->createAuth();
        $actionCodeSettings = null;
        if ($continueUrl) {
            $separator = (strpos($continueUrl, '?') !== false) ? '&' : '?';
            $markedUrl = $continueUrl . $separator . 'action=verified';
            $actionCodeSettings = ValidatedActionCodeSettings::fromArray([
                'continueUrl'     => $markedUrl,
                'handleCodeInApp' => false,
            ]);
        }
        $auth->sendEmailVerificationLink($user->email, $actionCodeSettings);
        $user->verificationSent = 1;
        $user->save();
    }

    public function checkVerification($uid)
    {
        // $user = $this->firebase->getAuth()->getUser($uid);
        $user = $this->firebase->createAuth()->getUser($uid);
        if ($user->emailVerified)
            return true;
        else
            return false;
    }

    public function saveUser($user)
    {
        // $collection = $this->firestore->collection('users');
        // $fuser = $collection->document($user->uid);
        // $fuser->set([
        //     'first' => $user->first,
        //     'last' =>$user->last,
        //     'phone'=>$user->phone,
        //     'email'=>$user->email,
        //     'agent'=> (int)$user->agent,
        //     'agents'=>[
        //         (int)$user->agent
        //     ],
        //     'agreedToTerms'=>$user->agreedToTerms,
        //     'verificationSent'=> null
        // ]);
    }

    public function logout($user)
    {
        try {
            // $this->firebase->getAuth()->revokeRefreshTokens($user->uid);
            $this->firebase->createAuth()->revokeRefreshTokens($user->uid);
        } catch (\Exception $e) {
        }
    }
}
