<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////

function BenchUpdateChips()
{
	require_once(app()->getModulePath().'/bench/functions.php');

	// some data cleanup tasks...
	dborun("UPDATE benchmarks SET device=TRIM(device) WHERE type='cpu'");
	dborun("UPDATE benchmarks SET power=NULL WHERE power<=3");
	dborun("UPDATE benchmarks SET plimit=NULL WHERE plimit=0");
	dborun("UPDATE benchmarks SET freq=NULL WHERE freq=0");
	dborun("UPDATE benchmarks SET memf=NULL WHERE memf=0");
	dborun("UPDATE benchmarks SET realmemf=NULL WHERE realmemf<=100");
	dborun("UPDATE benchmarks SET realfreq=NULL WHERE realfreq<=200");
	// bug in nvml 378.x and 381.x (linux + win) fixed in 382.05
	dborun("UPDATE benchmarks SET realfreq=NULL WHERE realfreq<=200 AND driver LIKE '% 378.%'");
	dborun("UPDATE benchmarks SET realfreq=NULL WHERE realfreq<=200 AND driver LIKE '% 381.%'");

	$benchs = getdbolist('db_benchmarks', "IFNULL(chip,'')=''");
	foreach ($benchs as $bench) {
		if (empty($bench->vendorid) || empty($bench->device)) continue;

		$dups = getdbocount('db_benchmarks', "vendorid=:vid AND client=:client AND os=:os AND driver=:drv AND throughput=:thr AND userid=:uid",
			array(':vid'=>$bench->vendorid, ':client'=>$bench->client, ':os'=>$bench->os, ':drv'=>$bench->driver,':thr'=>$bench->throughput,':uid'=>$bench->userid)
		);
		if ($dups > 10) {
			//debuglog("bench: {$bench->device} ignored ($dups records already present)");
			$bench->delete();
			continue;
		}

		$chip = getChipName($bench->attributes);
		debuglog("bench: {$bench->device} ($chip)...");
		if (!empty($chip) && $chip != '-') {
			$bench->chip = $chip;
			$bench->save();
		}
	}

	// add new chips
	$rows = dbolist('SELECT DISTINCT B.chip, B.type FROM benchmarks B WHERE B.chip NOT IN (
		SELECT DISTINCT C.chip FROM bench_chips C WHERE C.devicetype = B.type
	)');

	foreach ($rows as $row) {
		if (empty($row['chip']) || empty($row['type'])) continue;
		$chip = new db_bench_chips;
		$chip->chip = $row['chip'];
		$chip->devicetype = $row['type'];
		if ($chip->insert()) {
			debuglog("bench: added {$chip->devicetype} chip {$chip->chip}");
			dborun('UPDATE benchmarks SET idchip=:id WHERE chip=:chip AND type=:type', array(
				':id'=>$chip->id, ':chip'=>$row['chip'], ':type'=>$row['type'],
			));
		}
	}

	// update existing ones
	$rows = dbolist('SELECT DISTINCT chip, type FROM benchmarks WHERE idchip IS NULL');
	foreach ($rows as $row) {
		if (empty($row['chip']) || empty($row['type'])) continue;
		$chip = getdbosql('db_bench_chips', 'chip=:name AND devicetype=:type', array(':name'=>$row['chip'], ':type'=>$row['type']));
		if (!$chip || !$chip->id) continue;
		dborun('UPDATE benchmarks SET idchip=:id WHERE chip=:chip AND type=:type', array(
			':id'=>$chip->id, ':chip'=>$row['chip'], ':type'=>$row['type'],
		));
	}

}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
