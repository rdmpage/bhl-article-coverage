<?php

// just fetch title and item data for a list of titles (for stats)


$config['cache']   = dirname(__FILE__) . '/cache';
$config['api_key'] = '0d4f0303-712e-49e0-92c5-2113a5959159';


$stats = array();


//----------------------------------------------------------------------------------------
function get($url)
{
	$data = '';
	
	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	curl_setopt ($ch, CURLOPT_HEADER,		  1);  
	
	// timeout (seconds)
	curl_setopt ($ch, CURLOPT_TIMEOUT, 120);

	curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST,		  0);  
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,		  0);  
	
	$curl_result = curl_exec ($ch); 
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		// print_r($info);		
		 
		$header = substr($curl_result, 0, $info['header_size']);
		
		// echo $header;
		
		//exit();
		
		$data = substr($curl_result, $info['header_size']);
		
	}
	return $data;
}



//----------------------------------------------------------------------------------------

function get_item($ItemID, $force = false)
{
	global $config;
	
	global $stats;

	$colour_index = 0;
	
	// get BHL item
	$filename = $config['cache'] . '/' . $ItemID . '.json';

	if (!file_exists($filename) || $force)
	{
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetItemMetadata&itemid=' 
			. $ItemID . '&ocr=f&pages=t&parts=t&apikey=' . $config['api_key'] . '&format=json';
			
		echo $url . "\n";

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);
	$item_data = json_decode($json);
		
	
	
	return $item_data;


}


//----------------------------------------------------------------------------------------
// title
function get_title($TitleID, $force = false)
{
	global $config;
	global $stats;
	
	$filename = $config['cache'] . '/title-' . $TitleID . '.json';

	if (!file_exists($filename))
	{
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetTitleMetadata&titleid=' 
			. $TitleID . '&items=t&apikey=' . $config['api_key'] . '&format=json';

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);

	$title_data = json_decode($json);
	
	//print_r($title_data);
	
	//exit();
	
	$stats[$TitleID] = new stdclass;
	$stats[$TitleID]->id = $TitleID;
	$stats[$TitleID]->title = $title_data->Result->FullTitle;
	$stats[$TitleID]->scans = array();
	$stats[$TitleID]->num_dois = 0;
	$stats[$TitleID]->mean_part_length = 0;
	
	
	
	
	$items = array();
	
	foreach ($title_data->Result->Items as $item)
	{
		if (isset($item->Year))
		{
			if (!isset($stats[$TitleID]->scans[$item->Year]))
			{
				$stats[$TitleID]->scans[$item->Year] = new stdclass;
				$stats[$TitleID]->scans[$item->Year]->num_pages = 0;
				$stats[$TitleID]->scans[$item->Year]->num_parts = 0;
				$stats[$TitleID]->scans[$item->Year]->part_size = array();
				$stats[$TitleID]->scans[$item->Year]->mean_part_length = 0;
			}
		}
	
		$items[] = $item->ItemID;
	}
	
	$total_part_length 	= 0;
	$total_part_count 	= 0;
	$total_page_count 	= 0;
	
	foreach ($items as $item)
	{
		//echo "  Getting item $item...\n";
		$item_data = get_item($item, $force);
		
		if (isset($item_data))
		{
			$stats[$TitleID]->scans[$item_data->Result->Year]->num_pages += count($item_data->Result->Pages);
			
			$total_page_count += count($item_data->Result->Pages);
			
			if ($item_data->Result->Parts)
			{
				$stats[$TitleID]->scans[$item_data->Result->Year]->num_parts += count($item_data->Result->Parts);
				
				$total_part_count += count($item_data->Result->Parts);
			
				$sum = 0;
			
				foreach ($item_data->Result->Parts as $part)
				{
					// DOI?
					if (isset($part->Doi) && ($part->Doi != ""))
					{
						$stats[$TitleID]->num_dois++;
					}
				
					// get part size
					$s = 0;
					$e = 0;
				
					if (isset($part->StartPageNumber) && is_numeric($part->StartPageNumber))
					{
						$s = $part->StartPageNumber;
					}
					if (isset($part->EndPageNumber) && is_numeric($part->EndPageNumber))
					{
						$e = $part->EndPageNumber;
					}
				
					if (($s != 0) && ($e != 0))
					{
						$stats[$TitleID]->scans[$item_data->Result->Year]->part_size[] = ($e - $s + 1);
						
						$sum +=  ($e - $s + 1);
						
						$total_part_length += ($e - $s + 1);
					}
			
				}
				
				$stats[$TitleID]->scans[$item_data->Result->Year]->mean_part_length = $sum / count($item_data->Result->Parts);
			}
		}
		
		//print_r($item_data);
	}	
	
	$def_front_back_matter = 5; // allow for front and backmatter
	$def_mean_part_length = 10;
	
	$stats[$TitleID]->num_pages = $total_page_count;
	$stats[$TitleID]->num_parts = $total_part_count;
	$stats[$TitleID]->mean_part_length = 0;
	$stats[$TitleID]->expected_parts = 0;
	if ($total_part_count != 0)
	{
		$stats[$TitleID]->mean_part_length = round($total_part_length / $total_part_count);
		$stats[$TitleID]->expected_parts = 
			round( ($stats[$TitleID]->num_pages  - $def_front_back_matter)/ $stats[$TitleID]->mean_part_length);
	}
	else
	{
		// guess
		$stats[$TitleID]->expected_parts = 
			round( ($stats[$TitleID]->num_pages  - $def_front_back_matter)/ $def_mean_part_length);
		
	}
	
	// order by year
	ksort($stats[$TitleID]->scans);
		
	// print_r($stats);
	
	
	$row = array();
	
	$row[]=$stats[$TitleID]->id;
	$row[]=$stats[$TitleID]->title;
	$row[]=$stats[$TitleID]->num_pages;
	$row[]=$stats[$TitleID]->num_parts;
	$row[]=$stats[$TitleID]->mean_part_length;
	$row[]=$stats[$TitleID]->expected_parts;
	$row[]=$stats[$TitleID]->num_dois;
	
	echo join("\t", $row) . "\n";


}

//----------------------------------------------------------------------------------------

$titles = array(
//103162,
//116503,
//153166,
//128759, // Nuytsia
//62492,
//176213
141,
314,
480,
514,
600,
687,
730,
2087,
2197,
2198,
2202,
2359,
2510,
2680,
3119,
3882,
3943,
3966,
4181,
4274,
4949,
5067,
5361,
5943,
6170,
6440,
6524,
6928,
7411,
7414,
7519,
7542,
7544,
8074,
8089,
8113,
8115,
8128,
8145,
8269,
8641,
8648,
8649,
8796,
8981,
9028,
9424,
9479,
9494,
9614,
9697,
9698,
10009,
10088,
10241,
10603,
10776,
12268,
12411,
12678,
12908,
12920,
12931,
13041,
13264,
13398,
13855,
14019,
14109,
14373,
14688,
14971,
15400,
16038,
16059,
16197,
16211,
16255,
16355,
16515,
24065,
36618,
39684,
39849,
39988,
40214,
40896,
42204,
42254,
42255,
42256,
42670,
42858,
43746,
43750,
44478,
44720,
44979,
45400,
45466,
46370,
46639,
47024,
49174,
49392,
49914,
50012,
50067,
50228,
50446,
50899,
51984,
53542,
53832,
53833,
53882,
53883,
57946,
58640,
59883,
60455,
60982,
61449,
61654,
61893,
62169,
62492,
62642,
62643,
62815,
62896,
63274,
64180,
66573,
66841,
68618,
68619,
68672,
68686,
69296,
77306,
77367,
77508,
78194,
79640,
82240,
82295,
82521,
87610,
94758,
98946,
101582,
101952,
102724,
107243,
108657,
109981,
112965,
114382,
116503,
119522,
121359,
122512,
122696,
122978,
124505,
125400,
127489,
128759,
128777,
128790,
128797,
128809,
128832,
129346,
129988,
130490,
130872,
135556,
136214,
138348,
141270,
142219,
142573,
144396,
144635,
145272,
146497,
149559,
149832,
152577,
152647,
153166,
154059,
155000,
157010,
157691,
158815,
158834,
162187,
163263,
166349,
168316,
169110,
169282,
169356,
169357,
169397,
173847,
174750,
175306,
176213,

);


$force = false;
//$force = true;

$header = array(
'id','title','num_pages','num_parts','mean_part_length','expected_parts','num_dois'

);

echo join("\t", $header) . "\n";


foreach ($titles as $TitleID)
{
	//echo "Doing title $TitleID...\n";
	get_title($TitleID, $force);
}


?>