@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2" style="border-left: 3px solid blue;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="fs-5 fw-bold text-primary text-uppercase mb-1">Approved</div>
                                <div class="h4 mb-0 fw-bold text-secondary">{{ $approvedDoc}}</div>
                            </div>
                            <div class="col-auto">
                                <box-icon name='check' color="gray" size="lg"></box-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2" style="border-left: 3px solid green;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="fs-5 fw-bold text-success text-uppercase mb-1">Waiting</div>
                                <div class="h4 mb-0 fw-bold text-secondary">{{$waitingDoc}}</div>
                            </div>
                            <div class="col-auto">
                                <box-icon name='time' color="gray" size="lg"></box-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow h-100 py-2" style="border-left: 3px solid red;">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="fs-5 fw-bold text-danger text-uppercase mb-1">Rejected</div>
                                <div class="h4 mb-0 fw-bold text-secondary">{{$rejectedDoc}}</div>
                            </div>
                            <div class="col-auto">
                                <box-icon name='x-circle' color="gray" size="lg"></box-icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="container">
        <h3 class="my-3">Monthly Chart</h3>
        <div class="col-md-3">
            <select name="month" id="monthSelect" class="form-select">
                <option value="" disabled selected>--Select the month--</option>
                @for ($month = 1; $month <= 12; $month++)
                    <option value="{{ $month }}">{{ date('F', mktime(0, 0, 0, $month, 1)) }}</option>
                @endfor
            </select>
        </div>
    </div>

    <div class="container mt-3">
        <div class="container">
            <canvas id="myChart" aria-label="myChart" role="img"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@push('extraJs')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('myChart');
            let labels = [];
            let datas = [];

            const data = {
                labels: labels,
                datasets: [{
                    label: 'My First Dataset',
                    data: datas,
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                }]
            };

            const config = {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            min: 0,
                            max: 100
                        }
                    }
                }
            };

            const myChart = new Chart(ctx, config);

            const monthSelect = document.getElementById('monthSelect');

            // Set the monthSelect value to the current month
            const currentMonth = new Date().getMonth() + 1;
            monthSelect.value = currentMonth;

            // Function to update the chart based on the selected month
            function updateChart() {
                const monthIndex = parseInt(monthSelect.value) - 1; // Month index starts from 0
                const year = new Date().getFullYear(); // Get the current year

                // Get the first and last day of the selected month
                const firstDayOfMonth = new Date(year, monthIndex, 1);
                const lastDayOfMonth = new Date(year, monthIndex + 1, 0);

                // Calculate the number of weeks
                const numWeeks = Math.ceil((lastDayOfMonth.getDate() - firstDayOfMonth.getDate() + 1) / 7);

                // Generate labels for each week starting from Sunday
                labels = [];
                let currentDate = new Date(firstDayOfMonth);
                for (let i = 0; i < numWeeks; i++) {
                    const weekStart = new Date(currentDate);
                    const weekEnd = new Date(currentDate.setDate(currentDate.getDate() + 6));
                    labels.push(`Week ${i + 1}: ${weekStart.toLocaleDateString()} - ${weekEnd.toLocaleDateString()}`);
                    currentDate.setDate(currentDate.getDate() + 1); // Move to the next week
                }

                // Update chart data
                datas = Array.from({ length: numWeeks }, () => Math.floor(Math.random() * 100)); // Generate random data for each week
                myChart.data.labels = labels;
                myChart.data.datasets[0].data = datas;

                // Update the chart
                myChart.update();
            }

            // Call the updateChart function when the page loads and when the monthSelect value changes
            updateChart();
            monthSelect.addEventListener('change', updateChart);

            // Update chart when the window is resized
            window.addEventListener('resize', updateChart);
        });
    </script>

@endpush
