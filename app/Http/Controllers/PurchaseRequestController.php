<?php

namespace App\Http\Controllers;

use App\DataTables\DirectorPurchaseRequestDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseRequest;
use App\Models\DetailPurchaseRequest;
use App\Models\File;
use Illuminate\Support\Facades\Auth;
use App\Models\MonhtlyPR;
use Illuminate\Support\Facades\DB;
use App\Models\MasterDataPr;

class PurchaseRequestController extends Controller
{
    public function index(DirectorPurchaseRequestDataTable $datatable)
    {
        // Get user information
        $user = Auth::user();
        $userDepartmentName = $user->department->name;
        $isHRDHead = $userDepartmentName === "HRD" && $user->is_head === 1;

        // Determine conditions based on user department and role
        $purchaseRequestsQuery = PurchaseRequest::with('files', 'createdBy', 'createdBy.department');

        if ($isHRDHead) {
            // If the user is HRD Head, filter requests with specific conditions
            $purchaseRequestsQuery->whereNotNull('autograph_1')
                ->whereNotNull('autograph_2')
                ->whereNull('autograph_3')
                ->where('status', 2);

            $purchaseRequestsQuery;
        } elseif ($userDepartmentName === "DIRECTOR") {
            // If the user is a director, filter requests with specific conditions
            $purchaseRequestsQuery->whereNotNull('autograph_1')
                ->whereNotNull('autograph_2')
                ->whereNotNull('autograph_3')
                ->where('status', 3);
        } else {
            // Otherwise, filter requests based on user department
            $purchaseRequestsQuery->whereHas('createdBy.department', function ($query) use ($userDepartmentName) {
                $query->where('name', '=', $userDepartmentName);
            });
        }

        $purchaseRequests = $purchaseRequestsQuery
            ->orderByRaw('CASE WHEN status != -1 THEN 0 ELSE 1 END')
            ->orderBy('updated_at', 'desc')
            ->orderBy('status', 'desc')
            ->paginate(10);

        // // Get department-wise purchase request counts for chart
        // $departments = PurchaseRequest::select('to_department', DB::raw('COUNT(*) as count'))
        //     ->groupBy('to_department')
        //     ->get();

        // // Prepare data for the chart
        // $labels = $departments->pluck('to_department');
        // $counts = $departments->pluck('count');

        // return view('purchaseRequest.index', compact('labels', 'counts', 'purchaseRequests'));
        // return view('purchaseRequest.index', compact('purchaseRequests'));
        return $datatable->render('purchaseRequest.index', compact('purchaseRequests'));
    }

    public function getChartData(Request $request, $year, $month)
    {
        $purchaseRequests = PurchaseRequest::select('to_department', DB::raw('COUNT(*) as count'))
            ->whereYear('date_pr', $year)
            ->whereMonth('date_pr', $month)
            ->groupBy('to_department')
            ->get();

        $labels = $purchaseRequests->pluck('to_department');
        $counts = $purchaseRequests->pluck('count');

        return response()->json(['labels' => $labels, 'counts' => $counts]);
    }

    public function create()
    {
        $master = MasterDataPr::get();
        // dd($master);
        return view('purchaseRequest.create', compact('master'));
    }



    public function insert(Request $request)
    {

        $userIdCreate = Auth::id();

        // pr header
        $purchaseRequest = PurchaseRequest::create([
            'user_id_create' => $userIdCreate,
            'to_department' => $request->input('to_department'),
            'date_pr' => $request->input('date_of_pr'),
            'date_required' => $request->input('date_of_required'),
            'remark' => $request->input('remark'),
            'supplier' => $request->input('supplier'),
            'autograph_1' => strtoupper(Auth::user()->name) . '.png',
            'autograph_user_1' => Auth::user()->name,
            'status' => 1,

        ]);

        $prNo = substr($request->input('to_department'), 0, 4) . '-' . $purchaseRequest->id;
        $purchaseRequest->update(['pr_no' => $prNo]);

        // update revisi 26 februari
        $this->verifyAndInsertItems($request, $purchaseRequest->id);

        // update revisi 26 februari

        return redirect()->route('purchaserequest.home')->with('success', 'Purchase request created successfully');
    }

    private function verifyAndInsertItems($request, $id){
        if ($request->has('items') && is_array($request->input('items'))) {
            foreach ($request->input('items') as $itemData) {
                $itemName = $itemData['item_name'];
                $quantity = $itemData['quantity'];
                $purpose = $itemData['purpose'];
                $price = $itemData['price'];

                // Check if the item exists in MasterDataPr
                $existingItem = MasterDataPr::where('name', $itemName)->first();

                if (!$existingItem) {
                    // Case 1: Item not available in MasterDataPr
                    $newItem = MasterDataPr::create([
                        'name' => $itemName,
                        'price' => $price, // Store the initial price
                    ]);

                    // Create the DetailPurchaseRequest record
                    DetailPurchaseRequest::create([
                        'purchase_request_id' => $id,
                        'item_name' => $itemName,
                        'quantity' => $quantity,
                        'purpose' => $purpose,
                        'price' => $price,
                    ]);
                } else {
                    // Case 2: Item available in MasterDataPr

                    // ngecek harga yang sudah ada di latest price = null
                    if ($existingItem->latest_price === null){
                        // Check if the price is different
                        if ($existingItem->price != $price) {

                            if ($existingItem->latest_price === null) {
                                // Update the latest price if it's null
                                $existingItem->update(['latest_price' => $price]);

                                    // Create the DetailPurchaseRequest record
                                DetailPurchaseRequest::create([
                                    'purchase_request_id' => $id,
                                    'item_name' => $itemName,
                                    'quantity' => $quantity,
                                    'purpose' => $purpose,
                                    'price' => $price,
                                ]);
                            } else {
                                // Move the latest price to the price column
                                $existingItem->update(['price' => $existingItem->latest_price]);

                                // Update the latest price
                                $existingItem->update(['latest_price' => $price]);

                                // Create the DetailPurchaseRequest record
                                DetailPurchaseRequest::create([
                                    'purchase_request_id' => $id,
                                    'item_name' => $itemName,
                                    'quantity' => $quantity,
                                    'purpose' => $purpose,
                                    'price' => $price,
                                ]);
                            }
                        } else{
                            DetailPurchaseRequest::create([
                                'purchase_request_id' => $id,
                                'item_name' => $itemName,
                                'quantity' => $quantity,
                                'purpose' => $purpose,
                                'price' => $price,
                            ]);
                        }
                    }else{
                        // ngecek karena sudah ada latest price, maka acuan harga yang dilihat latest_price
                        if ($existingItem->latest_price != $price) {

                            if ($existingItem->latest_price === null) {
                                // Update the latest price if it's null
                                $existingItem->update(['latest_price' => $price]);

                                    // Create the DetailPurchaseRequest record
                                DetailPurchaseRequest::create([
                                    'purchase_request_id' => $id,
                                    'item_name' => $itemName,
                                    'quantity' => $quantity,
                                    'purpose' => $purpose,
                                    'price' => $price,
                                ]);
                            } else {

                                // Move the latest price to the price column
                                $existingItem->update(['price' => $existingItem->latest_price]);

                                // Update the latest price
                                $existingItem->update(['latest_price' => $price]);

                                // Create the DetailPurchaseRequest record
                                DetailPurchaseRequest::create([
                                    'purchase_request_id' => $id,
                                    'item_name' => $itemName,
                                    'quantity' => $quantity,
                                    'purpose' => $purpose,
                                    'price' => $price,
                                ]);
                            }
                        } else {
                            DetailPurchaseRequest::create([
                                'purchase_request_id' => $id,
                                'item_name' => $itemName,
                                'quantity' => $quantity,
                                'purpose' => $purpose,
                                'price' => $price,
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function viewAll()
    {
        // dd($purchaseRequest);
        return view('purchaseRequest.viewAll', compact('purchaseRequests'));
    }

    public function detail($id)
    {
        $purchaseRequest = PurchaseRequest::with('itemDetail', 'itemDetail.master')->find($id);
        foreach ($purchaseRequest->itemDetail as $detail) {
            $priceBefore = MasterDataPr::where('name', $detail->item_name)->first()->price;
        }
        // dd($priceBefore);
        $user =  Auth::user();
        $userCreatedBy = $purchaseRequest->createdBy;

        // dd($priceBefore);

           // Check if autograph_2 is filled
        if($purchaseRequest->status != -1){
            if ($purchaseRequest->autograph_2 !== null) {
                $purchaseRequest->status = 2;
            }

            // Check if autograph_3 is also filled
            if ($purchaseRequest->autograph_3 !== null) {
                $purchaseRequest->status = 3;
            }

            // Check if autograph_4 is also filled
            if ($purchaseRequest->autograph_4 !== null) {
                $purchaseRequest->status = 4;
            }
        }

        // Save the updated status
        $purchaseRequest->save();


        $timestamp = strtotime($purchaseRequest->created_at);
        $formattedDate = date("Ymd", $timestamp);
        $doc_id = 'PR/' . $purchaseRequest->id . '/' .$formattedDate;

        $files = File::where('doc_id', $doc_id)->get();

        return view('purchaseRequest.detail', compact('purchaseRequest', 'user', 'userCreatedBy', 'files'));
    }

    public function saveImagePath(Request $request, $prId, $section)
    {
        $username = Auth::check() ? Auth::user()->name : '';
        $imagePath = $username . '.png';

        // Save $imagePath to the database for the specified $reportId and $section
        $pr = PurchaseRequest::find($prId);
            $pr->update([
                "autograph_{$section}" => $imagePath
            ]);
            $pr->update([
                "autograph_user_{$section}" => $username
            ]);

        return response()->json(['success' => 'Autograph saved successfully!']);
    }


    public function monthlyview()
    {
        $purchaseRequests = PurchaseRequest::with('itemDetail')->get();

        return view('purchaseRequest.monthly', compact('purchaseRequests'));

    }


    public function monthlyviewmonth(Request $request)
    {

        // Get the month inputted by the user
        $selectedMonth = $request->input('month');


        // Extract year and month from the selected month input
        $year = date('Y', strtotime($selectedMonth));
        $month = date('m', strtotime($selectedMonth));


        // Save the year and month to the MonhtlyPR model
        MonhtlyPR::create([
            'month' => $month,
            'year' => $year,
            // Add other fields as needed
        ]);

        // Fetch purchase requests for the selected month
        $purchaseRequests = PurchaseRequest::with('itemDetail')
            ->whereYear('date_pr', $year)
            ->whereMonth('date_pr', $month)
            ->get();

        // Pass the filtered data to the view

        return view('purchaseRequest.monthly', compact('purchaseRequests'));
    }


    public function monthlyprlist()
    {
        $monthlist = MonhtlyPR::get();

        return view ('purchaseRequest.monthlylist', compact('monthlist'));
    }


    public function monthlydetail($id)
    {
        $monthdetail = MonhtlyPR::find($id);

         // Extract year and month from the selected month input
        // $year = date('Y', strtotime($monthdetail->year));
        // $month = date('m', strtotime($monthdetail->month));

        $year = $monthdetail->year;
        $month = $monthdetail->month;

        $purchaseRequests = PurchaseRequest::with('itemDetail')
        ->whereYear('date_pr', $year)
        ->whereMonth('date_pr', $month)
        ->get();

        // dd($monthdetail);
         return view('purchaseRequest.monthlydetail', compact('purchaseRequests', 'monthdetail'));
    }


    public function saveImagePathMonthly(Request $request, $monthprId, $section)
    {
        $username = Auth::check() ? Auth::user()->name : '';
        $imagePath = $username . '.png';

        // Save $imagePath to the database for the specified $reportId and $section
        $monthpr = MonhtlyPR::find($monthprId);
            $monthpr->update([
                "autograph_{$section}" => $imagePath
            ]);
            $monthpr->update([
                "autograph_user_{$section}" => $username
            ]);

        return response()->json(['success' => 'Autograph saved successfully!']);

    }



// REVISI PR DROPDOWN ITEM + PRICE
    public function getItemNames(Request $request)
    {
        $itemName = $request->query('itemName');
        info('AJAX request received for item name: ' . $itemName);

        // Fetch item names and prices from the database based on user input
        $items = MasterDataPr::where('name', 'like', "%$itemName%")
            ->select('name', 'price','latest_price')
            ->get();

        return response()->json($items);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'description' => 'string|max:255'
        ]);

        PurchaseRequest::find($id)->update([
            'status' => -1,
            'description' => $request->description
        ]);

        return redirect()->back()->with(['success' => 'Purchase Request rejected']);
    }

    public function edit($id){
        $pr = PurchaseRequest::find($id);
        $details = DetailPurchaseRequest::where('purchase_request_id', $id)->get();
        return view('purchaseRequest.edit', compact('pr', 'details'));
    }

    public function update(Request $request, $id){
        $validated= $request->validate([
            'to_department' => 'string|max:255',
            'date_of_pr' => 'date',
            'date_required' => 'date',
            'remark' => 'string',
            'supplier' => 'string',
        ]);

        PurchaseRequest::find($id)->update($validated);

        DetailPurchaseRequest::where('purchase_request_id', $id)->delete();

        $this->verifyAndInsertItems($request, $id);
        return redirect()->route('purchaserequest.home')->with(['success' => 'Purchase request updated successfully!']);
    }

    public function destroy($id){
        PurchaseRequest::find($id)->delete();
        DetailPurchaseRequest::where('purchase_request_id', $id)->delete();
        return redirect()->back()->with(['success' => 'Purchase request deleted succesfully!']);
    }

    public function approveSelected(Request $request){
        $ids = $request->input('ids', []);

        if(empty($ids)) {
            return response()->json(['message' => 'No records selected for approval. (server)']);
        } else {
            try {
                foreach ($ids as $id) {
                    PurchaseRequest::find($id)->update(['status' => 4]);
                }
                return response()->json(['message'=>'selected records approved successfully. (server)']);
            } catch (\Throwable $th) {
                return response()->json(['message'=>'failed to approve selected records. (server)']);
                throw $th;
            }
        }

    }

    public function rejectSelected(Request $request){
        $ids = $request->input('ids', []);
        $rejectionReason = $request->input('rejection_reason');

        if(empty($ids)) {
            return response()->json(['message' => 'No records selected for rejection. (server)']);
        }

        try {
            foreach ($ids as $id) {
                PurchaseRequest::find($id)->update([
                    'status' => -1,
                    'description' => $rejectionReason
                ]);
            }
            return response()->json(['message'=>'selected records rejected successfully. (server)']);
        } catch (\Throwable $th) {
            return response()->json(['message'=>'failed to reject selected records. (server)']);
            throw $th;
        }
    }

}
