<style type="text/css">
	.fc-request-parameters{
		display: inline-block;
		background: #dcdcdc;
		padding: 5px;
		font-family: monospace;
	}
  .log-pagination-wrapper .page-numbers{
    text-decoration: none;
    font-size: 16px;
  }
  .log-pagination-wrapper .page-numbers.current{
    font-weight: 600;
    font-size: 17px;
  }
  .log-pagination-text{
    font-size: 16px;
    font-weight: 500;
  }
</style>
<div class="wrap">
  <table class="wp-list-table widefat plugins" >
    <tr>
      <th><?php _e('S.No.', FRESH_TEXT_DOMAIN); ?></th>
      <th><?php _e('Activity Type', FRESH_TEXT_DOMAIN); ?></th>
      <th><?php _e('Request Status', FRESH_TEXT_DOMAIN); ?></th>
      <th><?php _e('Response Message', FRESH_TEXT_DOMAIN); ?></th>
      <th><?php _e('Requested At', FRESH_TEXT_DOMAIN); ?></th>
      <th><?php _e('Request Parameters', FRESH_TEXT_DOMAIN); ?></th>
    </tr>
    <?php foreach ($requests_log as $key => $log) { ?>
		<tr>
		  	<td><?php echo $key+1 . '.'; ?></td>
		  	<td><?php _e($log['activity_type'], FRESH_TEXT_DOMAIN); ?></td>
		  	<td><?php _e($log['request_status'], FRESH_TEXT_DOMAIN); ?></td>
		  	<td><?php _e($log['response_message'], FRESH_TEXT_DOMAIN); ?></td>
		  	<td><?php _e($log['requested_at'], FRESH_TEXT_DOMAIN); ?></td>
		  	<td>
		  		<?php if ( !is_null( $log['request_parameters'] ) ) { ?>
		  			<span class="fc-request-parameters"><?php _e($log['request_parameters'], FRESH_TEXT_DOMAIN); ?></span>
				<?php } ?>
		  	</td>
		</tr>
    <?php } ?>
  </table>
</div>
<br>