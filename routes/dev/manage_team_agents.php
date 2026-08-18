<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


if (!class_exists('TeamMemberAgent')) {
    class TeamMemberAgent extends \Illuminate\Database\Eloquent\Model {
        use \Illuminate\Database\Eloquent\SoftDeletes;
        protected $table = 'team_members';
        protected $fillable = ['id','team_id','agent_id','first','last','email','agency','languages','phone','bccondos_phone','profile_image','cities','areas','subarea','active','mlsid','mls_active'];

    }
}



Route::prefix('pixi-team')->group(function () {

    /*
    Route::get('/manage-agents/get-all', function () {
        @header("Access-Control-Allow-Origin: *");
        $agents = \Illuminate\Support\Facades\DB::table('team_members')->get();
        $fields = \Illuminate\Support\Facades\Schema::getColumnListing('team_members');
        $fields2 = \Illuminate\Support\Facades\DB::select('DESCRIBE team_members');
        return response()->json(['fields' => $fields??null, 'fields2' => $fields2??null, 'data' => $agents,]);
    });
    */

    // Access is enforced by RestrictDevRoutes on the parent /test group
    // (routes/dev/tester.php), which accepts X-Admin-Secret, an allowlisted
    // IP, or a dev-dj-approve session. The inline gate that used to live here
    // was redundant once the group middleware landed.
    Route::get('/manage-agents', function () {
        $file = public_path('d1/manage-agents-dashboard/index.html');
        return file_exists($file)
        ? response(str_replace(['="./assets/'], ['="/d1/manage-agents-dashboard/assets/'], file_get_contents($file)))->header('Content-Type', 'text/html')
        : response()->json(['error' => 'Dashboard not found!'], 404);
    });

    Route::get('/api/agents', function () {
        // $agents = file_exists($file = base_path('app/Helpers/agents_array.php')) ? include $file : [];
        $agents = (new \TeamMemberAgent)->all();
        return response()->json($agents);
    });

    Route::post('/api/agents/delete/{agent?}', function (Request $request, ?TeamMemberAgent $agent=null) {
        @header("Access-Control-Allow-Origin: *");
        $_retMsg=['status'=>'error','error'=>'failed!'];
        if(rand(0,1)/*$agent?->delete()*/){
            $_retMsg=['status'=>'success','message'=> ($agent?->first??'Agent') /*.' (id:'.($agent?->id).')'*/ . ' deleted'];
        }
        // $_retMsg=['status'=>'success','message'=>($agent?->id??'Agent').' deleted'];
        return response()->json($_retMsg);
    });
    Route::post('/api/agents/add', function (Request $request, ?TeamMemberAgent $agent=null) {
        @header("Access-Control-Allow-Origin: *");
        $_retMsg=['status'=>'error','error'=>'failed!'];
        if(rand(0,1)/*$agent?->delete()*/){
            $_retMsg=['status'=>'success','message'=> ($agent?->first??'Agent') /*.' (id:'.($agent?->id).')'*/ . ' add'];
        }
        // $_retMsg=['status'=>'success','message'=>($agent?->id??'Agent').' add'];
        return response()->json($_retMsg);
    });
    Route::post('/api/agents/update/{agent?}', function (Request $request, ?TeamMemberAgent $agent=null) {
        // This writes an uploaded image into the public webroot at a path
        // derived from caller-supplied first/last, so the payload is validated
        // before GD ever sees it.
        $request->validate([
            'first' => ['required','string','max:60'],
            'last'  => ['required','string','max:60'],
            'image' => ['nullable','image','mimes:jpeg,png','max:5120','dimensions:max_width=4000,max_height=4000'],
        ]);


        $agents = file_exists($file = base_path('app/Helpers/agents_array.php')) ? include $file : [];

        $slug = \Illuminate\Support\Str::slug('ta_'.$request->first . '-' . $request->last);
        $filename = $slug . '.jpg';
        $path = public_path("frontend/images/teamagents/$filename");

        if (!$request->replace_img && file_exists($path)) {
            return response()->json(['status' => 'error', 'error' => 'File exists'], 409);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $img = imagecreatefromstring(file_get_contents($image));
            $width = imagesx($img);
            $height = imagesy($img);
            $size = min($width, $height);
            $cropped = imagecrop($img, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
            imagejpeg($cropped, $path, 80);
        }

        $_newAgentData = [
            'first' => $request->first,
            'last' => $request->last,
            'profile_image' => url("frontend/images/teamagents/$filename"),
            'languages' => $request->languages,
            'tel' => $request->tel,
            'email' => $request->email,
            'video' => $request->video ?? '',
            'visible' => $request->boolean('visible',true),
        ];

        if(($_editIndex = $request->input('editIndex',null))!==null){
            $agents[$_editIndex] = $_newAgentData;
            $_retMsg = 'updated ' . ($agents[$_editIndex]['first']??'');
        }else{
            $agents []= $_newAgentData;
            $_retMsg = 'added';
        }

        // file_put_contents($file, "<?php\n\$agents = " . var_export($agents, true) . ";\nreturn isset(\$_GET['show_all_agents']) ? \$agents : array_filter(\$agents, fn(\$a) => \$a['visible']??true);");
        // older: // file_put_contents($file, "<?php\n\$agents = json_decode('" . json_encode($agents, JSON_PRETTY_PRINT) . "', true);\nreturn isset(\$_GET['show_all_agents']) ? \$agents : array_filter(\$agents, fn(\$a) => \$a['visible'] ?? true);");
        
        // if($agent){ 
        //     $_retMsg='updated '.$_newAgentData['first'].' '.$_newAgentData['last'];
        // }else{}

        return response()->json(['status' => 'success', 'message' => $_retMsg??'saved', 'filename' => $filename]);
    });
});
