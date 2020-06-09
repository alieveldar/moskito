<?='<?xml version="1.0" encoding="utf-8"?>'?>
<base>
	<tour>
		<code><?=htmlspecialchars($data['code'])?></code>
		<name><?=htmlspecialchars($data['name'])?></name>
		<excerpt><![CDATA[<?=$data['excerpt']?>]]></excerpt>
		<description><![CDATA[<?=$data['description']?>]]></description>
		<highlights><?php
			if(is_array($data['highlights']) && $data['highlights']>0) foreach($data['highlights'] as $h) : ?>
				<item><?=htmlspecialchars($h)?></item>
			<?php endforeach;
		?></highlights>
		<duration><?=htmlspecialchars($data['duration'])?></duration>
		<extension><?=htmlspecialchars($data['extension'])?></extension>
		<group><?=htmlspecialchars($data['group'])?></group>
		<price><?=htmlspecialchars($data['price'])?></price>
		<map><![CDATA[<?=$data['map']?>]]></map>
		<rating><?=$data['rating']?></rating>
		<requirement><![CDATA[<?=$data['requirement']?>]]></requirement>
		<individual><?php
			if(is_array($data['individual']) && $data['individual']>0) foreach($data['individual'] as $ind) : ?>			
				<item>
					<num><?=$ind['num']?></num>
					<num_name><?=htmlspecialchars($ind['num_name'])?></num_name>
					<title><![CDATA[<?=$ind['title']?>]]></title>
					<text><![CDATA[<?=$ind['text']?>]]></text>
				</item>
			<?php endforeach;
		?></individual>
		<service><?php
			if(is_array($data['services']) && $data['services']>0) foreach($data['services'] as $s) : ?>
				<item><?=htmlspecialchars($s)?></item>
			<?php endforeach;
		?></service>
		<extras><?php
			if(is_array($data['extras']) && $data['extras']>0) foreach($data['extras'] as $ex) : ?>
				<item><?=htmlspecialchars($ex)?></item>
			<?php endforeach;
		?></extras>
		<additional><![CDATA[<?=$data['additional']?>]]></additional>
		<operator><?=htmlspecialchars($data['operator'])?></operator>
		<dates><?php
			if(is_array($data['dates']) && $data['dates']>0) foreach($data['dates'] as $date) : ?>
				<date><?=$date?></date>			
			<?php endforeach;		
		?></dates>
		<guides><?php
			if(is_array($data['guides']) && $data['guides']>0) foreach($data['guides'] as $guide) : ?>
				<item>
					<name><?=htmlspecialchars($guide['name'])?></name>
					<description><![CDATA[<?=$guide['description']?>]]></description>	
				</item>					
			<?php endforeach;
		?></guides>
		<preview><?=$data['preview']?></preview>
		<images><?php
			if(is_array($data['images']) && $data['images']>0) foreach($data['images'] as $image) : ?>
				<item><?=htmlspecialchars($image)?></item>
			<?php endforeach;
		?></images>
		<pricetermine><?php
			if(is_array($data['prices']) && $data['prices']>0) foreach($data['prices'] as $price) : ?>
				<item>
					<date><?=$price['date']?></date>
					<tage><?=$price['tage']?></tage>
					<price><?=$price['price']?></price>
					<text><![CDATA[<?=$price['text']?>]]></text>
				</item>			
			<?php endforeach;
		?></pricetermine>
		<pricetext><?=$data['price_text']?></pricetext>
		<destco><?=$data['destco']?></destco>
	</tour>
</base>
