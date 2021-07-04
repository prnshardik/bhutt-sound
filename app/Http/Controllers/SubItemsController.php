<?php    
    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Str;
    use App\Models\SubItem;
    use App\Models\SubItemCategory;
    use App\Http\Requests\SubItemRequest;
    use Auth, Validator, DB, Mail, DataTables, File;

    class SubItemsController extends Controller{
        /** index */
            public function index(Request $request){
                if($request->ajax()){
                    $data = SubItem::select('sub_items.id', 'sub_items_categories.title as category', 'sub_items.name', 'sub_items.image', 'sub_items.qrcode', DB::Raw("SUBSTRING(".'sub_items.description'.", 1, 30) as description"), 'sub_items.status')
                                    ->leftjoin('sub_items_categories', 'sub_items_categories.id', 'sub_items.category_id')
                                    ->get();

                    return Datatables::of($data)
                            ->addIndexColumn()
                            ->addColumn('action', function($data){
                                return ' <div class="btn-group btn-sm">
                                                <a href="'.route('sub-items.view', ['id' => base64_encode($data->id)]).'" class="btn btn-default btn-xs">
                                                    <i class="fa fa-eye"></i>
                                                </a> 
                                                <a href="'.route('sub-items.edit', ['id' => base64_encode($data->id)]).'" class="btn btn-default btn-xs">
                                                    <i class="fa fa-edit"></i>
                                                </a>  
                                                <a href="javascript:;" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                                    <i class="fa fa-bars"></i>
                                                </a> 
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="javascript:;" onclick="change_status(this);" data-status="active" data-old_status="'.$data->status.'" data-id="'.base64_encode($data->id).'">Active</a></li>
                                                    <li><a class="dropdown-item" href="javascript:;" onclick="change_status(this);" data-status="inactive" data-old_status="'.$data->status.'" data-id="'.base64_encode($data->id).'">Inactive</a></li>
                                                    <li><a class="dropdown-item" href="javascript:;" onclick="change_status(this);" data-status="deleted" data-old_status="'.$data->status.'" data-id="'.base64_encode($data->id).'">Delete</a></li>
                                                    <li><a class="dropdown-item" href="'.route('sub-items.print', ['id' =>base64_encode($data->id)]).'">Print QR Code</a></li>
                                                </ul>
                                            </div>';
                            })

                            ->editColumn('status', function($data) {
                                if($data->status == 'active')
                                    return '<span class="badge badge-pill badge-success">Active</span>';
                                else if($data->status == 'inactive')
                                    return '<span class="badge badge-pill badge-warning">Inactive</span>';
                                else if($data->status == 'deleted')
                                    return '<span class="badge badge-pill badge-danger">Delete</span>';
                                else
                                    return '-';
                            })

                            ->editColumn('image', function($data) {
                                if($data->image != null || $data->image != '')
                                    $image = url('uploads/sub_items').'/'.$data->image;
                                else
                                    $image = url('uploads/sub_items').'/default.png';
                                
                                return "<img src='$image' style='height: 30px; width: 30px'>";
                            })

                            ->editColumn('qrcode', function($data) {
                                if($data->qrcode != null || $data->qrcode != '')
                                    $image = url('uploads/qrcodes/sub_items').'/'.$data->qrcode;
                                else
                                    $image = '';
                                
                                return "<img src='$image' style='height: 30px; width: 30px'>";
                            })

                            ->rawColumns(['action', 'status', 'image', 'qrcode'])
                            ->make(true);
                }
                return view('sub-items.items.index');
            }
        /** index */

        /** create */
            public function create(Request $request){
                $categories = SubItemCategory::select('id', 'title')->where(['status' => 'active'])->get();
                return view('sub-items.items.create', ['categories' => $categories]);
            }
        /** create */

        /** insert */
            public function insert(SubItemRequest $request){
                if($request->ajax()){ return true; }

                if(!empty($request->all())){
                    $file_to_uploads = public_path().'/uploads/sub_items/';
                    if (!File::exists($file_to_uploads))
                        File::makeDirectory($file_to_uploads, 0777, true, true);

                    $qr_to_uploads = public_path().'/uploads/qrcodes/sub_items/';
                    if (!File::exists($qr_to_uploads))
                        File::makeDirectory($qr_to_uploads, 0777, true, true);

                    DB::beginTransaction();
                    try {
                        $names = [];
                        $qrnames = [];
                        $quantity = $request->quantity ?? 1;
                        $i = 0;

                        while($i < $quantity){
                            $crud = [
                                'category_id' => $request->category_id,
                                'name' => ucfirst($request->name),
                                'description' => $request->description ?? NULL,
                                'status' => 'active',
                                'created_at' => date('Y-m-d H:i:s'),
                                'created_by' => auth()->user()->id,
                                'updated_at' => date('Y-m-d H:i:s'),
                                'updated_by' => auth()->user()->id
                            ];
    
                            if(!empty($request->file('image'))){
                                $file = $request->file('image');
                                $filenameWithExtension = $request->file('image')->getClientOriginalName();
                                $filename = pathinfo($filenameWithExtension, PATHINFO_FILENAME);
                                $extension = $request->file('image')->getClientOriginalExtension();
                                $filenameToStore = time()."_".$filename.'.'.$extension;
    
                                $crud["image"] = $filenameToStore;

                                array_push($names, $filenameToStore);
                            }else{
                                $crud["image"] = 'default.png';
                            }
    
                            $last_id = SubItem::insertGetId($crud);

                            if($last_id){
                                $qrname = 'qrcode_'.$last_id.'.png';
                                array_push($qrnames, $qrname);
    
                                \QrCode::size(500)->format('png')->merge('/public/qr_logo.png', .3)->generate($last_id, public_path('uploads/qrcodes/sub_items/'.$qrname));
    
                                $update = SubItem::where(['id' => $last_id])->update(['qrcode' => $qrname]);
    
                                if($update){
                                    $i++;
                                    if(!empty($request->file('image')))
                                        File::copy($request->file('image'), public_path('/uploads/sub_items'.'/'.$filenameToStore));
                                }                                
                            }
                        }

                        if($i == $quantity){
                            DB::commit();
                            return redirect()->route('sub-items')->with('success', 'Record added successfully');
                        }else{
                            if(!empty($names)){
                                foreach($names as $name){
                                    @unlink(public_path().'/uploads/sub_items/'.$name);
                                }
                            }

                            if(!empty($qrnames)){
                                foreach($qrnames as $name){
                                    @unlink(public_path().'/uploads/qrcodes/sub_items/'.$name);
                                }
                            }

                            DB::rollback();
                            return redirect()->back()->with('error', 'Faild to add record\'s qrcode')->withInput();
                        }
                    } catch (\Exception $e) {
                        DB::rollback();
                        return redirect()->back()->with('error', 'Faild to add record')->withInput();
                    }
                }else{
                    return redirect()->back()->with('error', 'Something went wrong')->withInput();
                }
            }
        /** insert */

        /** view */
            public function view(Request $request, $id=''){
                if($id == '')
                    return redirect()->back()->with('error', 'Something went wrong');

                $id = base64_decode($id);
                $generate = _generate_qrcode($id, 'sub_item');

                if($generate){
                    $path = URL('/uploads/sub_items').'/';
                    $categories = SubItemCategory::select('id', 'title')->where(['status' => 'active'])->get();
                    $data = SubItem::select('id', 'category_id', 'name', 'description', 
                                        DB::Raw("CASE
                                        WHEN ".'image'." != '' THEN CONCAT("."'".$path."'".", ".'image'.")
                                        ELSE CONCAT("."'".$path."'".", 'default.png')
                                        END as image")
                                    )
                                    ->where(['id' => $id])
                                    ->first();
                    
                    if($data)
                        return view('sub-items.items.view', ['data' => $data, 'categories' => $categories]);
                    else
                        return redirect()->back()->with('error', 'No record found');
                }else{
                    return redirect()->back()->with('error', 'No record found');
                }
            }
        /** view */

        /** edit */
            public function edit(Request $request, $id=''){
                if($id == '')
                    return redirect()->back()->with('error', 'Something went wrong');

                $id = base64_decode($id);

                $path = URL('/uploads/sub_items').'/';
                $categories = SubItemCategory::select('id', 'title')->where(['status' => 'active'])->get();
                $data = SubItem::select('id', 'category_id', 'name', 'description', 
                                    DB::Raw("CASE
                                    WHEN ".'image'." != '' THEN CONCAT("."'".$path."'".", ".'image'.")
                                    ELSE CONCAT("."'".$path."'".", 'default.png')
                                    END as image")
                                )
                                ->where(['id' => $id])
                                ->first();

                if($data)
                    return view('sub-items.items.edit', ['data' => $data, 'categories' => $categories]);
                else
                    return redirect()->back()->with('error', 'No record found');
            }
        /** edit */ 

        /** update */
            public function update(SubItemRequest $request){
                if($request->ajax()){ return true; }

                if(!empty($request->all())){
                    $exst_record = SubItem::where(['id' => $request->id])->first(); 

                    $folder_to_upload = public_path().'/uploads/sub_items/';
                    if (!File::exists($folder_to_upload))
                        File::makeDirectory($folder_to_upload, 0777, true, true);

                    $crud = [
                        'category_id' => $request->category_id,
                        'name' => ucfirst($request->name),
                        'description' => $request->description ?? NULL,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'updated_by' => auth()->user()->id
                    ];

                    if(!empty($request->file('image'))){
                        $file = $request->file('image');
                        $filenameWithExtension = $request->file('image')->getClientOriginalName();
                        $filename = pathinfo($filenameWithExtension, PATHINFO_FILENAME);
                        $extension = $request->file('image')->getClientOriginalExtension();
                        $filenameToStore = time()."_".$filename.'.'.$extension;

                        $crud["image"] = $filenameToStore;
                    }else{
                        $crud["image"] = $exst_record->image;
                    }

                    $update = SubItem::where(['id' => $request->id])->update($crud);

                    if($update){
                        if(!empty($request->file('image')))
                            $file->move($folder_to_upload, $filenameToStore);

                        if($exst_record->image != null || $exst_record->image != ''){
                            $file_path = public_path().'/uploads/sub_items/'.$exst_record->image;

                            if(File::exists($file_path) && $file_path != ''){
                                if($exst_record->image != 'default.png')
                                    @unlink($file_path);
                            }
                        }

                        return redirect()->route('sub-items')->with('success', 'Record updated successfully');
                    }else{
                        return redirect()->back()->with('error', 'Faild to update record')->withInput();
                    }
                }else{
                    return redirect()->back()->with('error', 'Something went wrong')->withInput();
                }
            }
        /** update */

        /** change-status */
            public function change_status(Request $request){
                if(!$request->ajax()){ exit('No direct script access allowed'); }

                if(!empty($request->all())){
                    $id = base64_decode($request->id);
                    $status = $request->status;

                    $data = SubItem::where(['id' => $id])->first();

                    if(!empty($data)){
                        if($status == 'deleted')
                            $update = SubItem::where(['id' => $id])->delete();
                        else
                            $update = SubItem::where(['id' => $id])->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => auth()->user()->id]);
                        
                        if($update){
                            if($status == 'deleted'){
                                $file_path = public_path().'/uploads/sub_items/'.$data->image;

                                if(File::exists($file_path) && $file_path != ''){
                                    if($data->image != 'default.png')
                                        @unlink($file_path);
                                }

                                $qr_path = public_path().'/uploads/qrcodes/sub_items/'.$data->qrcode;

                                if(File::exists($qr_path) && $qr_path != ''){
                                    if($data->qrcode != 'default.png')
                                        @unlink($qr_path);
                                }
                            }
                            return response()->json(['code' => 200]);
                        }else{
                            return response()->json(['code' => 201]);
                        }
                    }else{
                        return response()->json(['code' => 201]);
                    }
                }else{
                    return response()->json(['code' => 201]);
                }
            }
        /** change-status */

        /** print */
            public function print(Request $request, $id=''){
                if($id == '')
                    return redirect()->back()->with('error', 'something went wrong');

                $id = base64_decode($id);

                $generate = _generate_qrcode($id, 'sub_item');

                if($generate){
                    $data = SubItem::select('qrcode')->where(['id' => $id])->first();
                
                    if($data)
                        return view('sub-items.items.print', ['data' => $data]);
                    else
                        return redirect()->back()->with('error', 'Something went wrong');    
                }else{
                    return redirect()->back()->with('error', 'something went wrong');
                }   
            }
        /** print */
    }