<div class="wrap wp-bkash-wrapper">
    <table id="myTable" class="display">
        <thead>
            <tr>
                <th>Sl.</th>
                <th>Order No.</th>
                <th>Paid By</th>
                <th>TRX ID</th>
                <th>Amount</th>
                <th>Sender</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            foreach ($items as $item) {
            ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= $item->order_id ?></td>
                    <td><?= ucwords($item->invoice_id) ?></td>
                    <td><?= $item->trx_id ?></td>
                    <td><?= $item->amount ?></td>
                    <td><?= $item->payment_id ?></td>
                    <td><?= $item->status ?></td>
                    <td><?= $item->datetime ?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</div>