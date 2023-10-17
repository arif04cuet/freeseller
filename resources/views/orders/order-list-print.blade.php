<style>
    @media print {
        * {
            display: none;
        }

        #printableTable {
            display: block;
        }

        #printableTable table {
            border: 1px;
            width: 90%
        }
    }
</style>
<script type="text/javascript">
    function printDiv() {
        window.frames["print_frame"].document.body.innerHTML = document.getElementById("printableTable").innerHTML;
        window.frames["print_frame"].window.focus();
        window.frames["print_frame"].window.print();
    }
</script>

scr


<div id="printableTable">
    <table style="width: 100%"
        class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
        <thead class="bg-gray-50 dark:bg-white/5">
            <tr style="border: 1px solid black; ">
                <th style="text-align: left;border: 1px">Order#</th>
                <th style="text-align: left;border: 1px">CN#</th>
                <th style="text-align: left;border: 1px">Reseller</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
            @foreach ($orders as $order)
                <tr style="border: 1px solid black; ">
                    <td class="text-left">{{ $order->id }}</td>
                    <td class="text-left">{{ $order->consignment_id }}</td>
                    <td class="text-left">{{ $order->reseller->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <br />
    <div>Total Parcel: {{ $orders->count() }}</div>
</div>

<iframe name="print_frame" width="0" height="0" frameborder="0" src="about:blank"></iframe>

<button class="text-primary-500"
    onclick="window.frames['print_frame'].document.body.innerHTML = document.getElementById('printableTable').innerHTML;
window.frames['print_frame'].window.focus();
window.frames['print_frame'].window.print(); return false">Print</button>
