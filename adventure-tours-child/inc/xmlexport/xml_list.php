<?='<?xml version="1.0" encoding="utf-8"?>'?>
<base>
	<tours>
		<?php if(is_array($data['tours']) && sizeOf($data['tours'])>0) foreach($data['tours'] as $tour) : ?>
			<tour>
				<code><?=$tour['code']?></code>
				<date><?=$tour['date']?></date>
				<link><?=$tour['link']?></link>
			</tour>
		<?php endforeach; ?>
	</tours>
</base>
