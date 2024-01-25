@extends('layouts.app')

@section('content')
<section class="header">
    <div class="d-flex mb-3 row-flex">
        <div class="h2 p-2 me-auto">QA/QC Reports</div>
    </div>
</section>

@if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
@endif

<section class="content">
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0 text-center table-striped">
                <thead>
                    <tr>
                      <th class="fs-5" scope="col">ID</th>
                      <th class="fs-5" scope="col">Invoice No</th>
                      <th class="fs-5" scope="col">Customer</th>
                      <th class="fs-5" scope="col">Verify Date</th>
                      <th class="fs-5" scope="col">Rec Date</th>
                      <th class="fs-5" scope="col">Action</th>
                      <th class="fs-5" scope="col">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($reports as $report)
                        @if ($report->autograph_1 && $report->autograph_2 && $report->autograph_3)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->invoice_no }}</td>
                            <td>{{ $report->customer }}</td>
                            <td>{{ $report->rec_date }}</td>
                            <td>{{ $report->verify_date }}</td>
                            <td class="text-start">
                                <a href="{{ route('direktur.qaqc.detail', ['id' => $report->id]) }}" class="btn btn-info" style="--bs-bg-opacity: .5;">View Details</a>
                                @if($report->attachment)
                                    @php
                                        $filename = basename($report->attachment);
                                    @endphp
                                    <a href="{{ asset('storage/attachments/' . $report->attachment) }}" class="btn btn-info" style="--bs-bg-opacity: .5;" download="{{ $filename }}">
                                        <i class='bx bx-download'></i>
                                        Download Attachment
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if($report->is_approve === 1)
                                    <span class="badge rounded-pill text-bg-success px-3 py-2 fs-6 fw-medium">APPROVED</span>
                                @elseif($report->is_approve === 0)
                                    <span class="badge rounded-pill text-bg-danger px-3 py-2 fs-6 fw-medium">REJECTED</span>
                                    @else
                                    <span class="badge rounded-pill text-bg-warning px-3 py-2 fs-6 fw-medium">WAITING</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                    @endforeach
                  </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
