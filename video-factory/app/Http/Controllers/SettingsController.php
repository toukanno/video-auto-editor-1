<?php

namespace App\Http\Controllers;

use App\Models\PlatformAccount;
use App\Support\VideoProcessingDefaults;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $accounts = PlatformAccount::where('user_id', $user->id)->get()->keyBy('platform');
        $profiles = VideoProcessingDefaults::profiles();

        return view('settings.index', compact('user', 'accounts', 'profiles'));
    }
}
