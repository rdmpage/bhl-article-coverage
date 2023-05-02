<?php


$config['cache']   = dirname(__FILE__) . '/cache';
$config['colours'] = array("#FF6600", "#6666CC", "#FFFF99", '#66FF66');
$config['no_colour'] = 'none';
$config['api_key'] = '0d4f0303-712e-49e0-92c5-2113a5959159';
$config['width']   = 800;
$config['height']  = 800;
$config['spacing'] = 0.1;


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
// https://math.stackexchange.com/a/466248
// Get size of square to fit num_pages squares inside a box width x height
function num_squares($num_pages, $width, $height)
{	
	// squares that can fit inside box
	
	$px = ceil(sqrt($num_pages * $width / $height));
	
	$sx = 0;
	$sy = 0;
	
	if (floor($px * $height / $width) * $px < $num_pages)
	{
		// does not fit
		$sx = $height / ceil($px * $height / $width);
	}
	else
	{
		$sx = $width / $px;
	}
	
	$py = ceil(sqrt($num_pages * $width / $height));
	if (floor($py * $width / $height) * $py < $num_pages)
	{
		// does not fit
		$sy = $width / ceil($py * $width / $height);
	}
	else
	{
		$sy = $height / $py;
	}
	
	$square = floor(max($sx, $sy));
	
	return $square;
}

//----------------------------------------------------------------------------------------
function draw_things_html($thing, $width, $height, $show_grid = false)
{
	global $config;
	
	// number of pages in item
	$n = count($thing->things);
	
	// size of square
	$square_size = num_squares($n, $width, $height);
	
	// spacing around image
	$space = floor($config['spacing'] * $square_size);	
	
	
	// html
	
	$html = '<html>';
	
	$html .= '<head>';
	
	if ($show_grid)
	{
		$html .= '<style>
			/* https://codepen.io/rjanjic/pen/dKKaXa */	
			.grid {
			background-image: linear-gradient(45deg, #dddddd 25%, transparent 25%), linear-gradient(-45deg, #dddddd 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #dddddd 75%), linear-gradient(-45deg, transparent 75%, #dddddd 75%);
		  background-size: 40px 40px;
		  background-position: 0 0, 0 20px, 20px -20px, -20px 0px;
		  }
		  </style>';
  	}
  	else
  	{
  		$html .= '<style>.grid{}</style>';  	
  	}
  
  	$html .= '</head>
  	<body>';
  
  	if (isset($thing->parentId))
  	{
  		$html .= '<div><a href="../' . $thing->parentId . '.html">Back to parent</a></div>';
  	}
  	
  	if (isset($thing->back))
  	{
  		$html .= '<div><a href="' . $thing->back . '">Back to parent</a></div>';
  	}  	
  
  	$html .= '<h1>' . $thing->name . '</h1>';
  	
  	if (isset($thing->description))
  	{
  		$html .= '<p>' . $thing->description . '</p>';
  	}	
	
	$html .= '<div class="grid" style="position:relative;width:' . $width . 'px;height:' . $height . 'px;">';
	
	foreach ($thing->things as $obj)
	{
		$html .= '<div style="position:relative;float:left;';
		
		if (isset($obj->colour))
		{
			$html .= 'background-color:' . $obj->colour . ';';
		}
		
		$html .= 'width:' . $square_size . 'px;height:' . $square_size . 'px;">';
		
		if (isset($obj->biostor))
		{
			$html .= '<a href="http://localhost/biostor-classic/www/page_range_editor.php?reference_id=' . $obj->biostor . '"';
		}
		else
		{
			$html .= '<a href="' . $obj->url . '"';
		}
		if (isset($obj->name))
		{
			$html .= ' title="' . $obj->name . '"';
		}
		$html .= ' target="_new" >';	
		
		if (isset($obj->imageUrl))
		{
			$html .= '<img style="object-fit:contain;position:absolute;top:' . $space . 'px;left:' . $space . 'px;width:' . ($square_size - 2 * $space) . 'px;height:' . ($square_size - 2 * $space) . 'px;" src="' . $obj->imageUrl . '" />';		
		}
		$html .= '</a>';
		$html .= '</div>';	
		
	
	}
		

	$html .= '</div>';
	$html .= '</body>';
	$html .= '</html>';


	return $html;
}

//----------------------------------------------------------------------------------------
// draw simple coloured grid of the objects
function draw_things_svg($thing, $width, $height)
{
	$svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="' . $height . '" width="' . $width . '">';

	$svg .= '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" fill="rgb(192,192,192)" />';

	// number of pages in item
	$n = count($thing->things);
	
	// size of square
	$square_size = num_squares($n, $width, $height);
	
	$num_cols = floor($width / $square_size);
	
	
	$count = 0;
	$row = 0;
	$col = 0;
	
	while ($count < $n)
	{
		$svg .= '<rect x="' . ($square_size * $col) . '" y="' . ($square_size * $row) . '" width="' . $square_size . '" height="' . $square_size . '" fill="' . $thing->things[$count]->colour . '" />';
		$col++;
		
		$count++;
		
		if ($col == $num_cols)
		{
			$row++;
			$col = 0;
		}
		
	}
	$svg .= '</svg>';
	
	return $svg;
}

//----------------------------------------------------------------------------------------
// draw grid of thumbnails of objects
function draw_things_svg_thumbnail($thing, $width, $height)
{

	// number of pages in item
	$n = count($thing->things);
	
	// size of square
	$square_size = num_squares($n, $width, $height);
	$num_cols = floor($width / $square_size);
	
	$svg_width = 64 * floor($width / $square_size);
	$svg_height = 64 * floor($height / $square_size);
	
	$svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="' . $svg_height . '" width="' . $svg_width . '">';

	$svg .= '<rect x="0" y="0" width="' . $svg_width . '" height="' . $svg_height . '" fill="rgb(192,192,192)" />';
	

	
	$count = 0;
	$row = 0;
	$col = 0;
	
	while ($count < $n)
	{
	
		$svg .= '<image x="' . (64 * $col) . '" y="' . (64 * $row) . '" href="' . $thing->id . '/' . $thing->things[$count]->id .  '.png" width="64" height="64" />';
		$col++;
		
		$count++;
		
		if ($col == $num_cols)
		{
			$row++;
			$col = 0;
		}
		
	}
	$svg .= '</svg>';
	
	return $svg;
}


//----------------------------------------------------------------------------------------

function get_item($ItemID, $force = false)
{
	global $config;

	$colour_index = 0;
	
	// get BHL item
	$filename = $config['cache'] . '/' . $ItemID . '.json';

	if (!file_exists($filename) || $force)
	{
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetItemMetadata&itemid=' 
			. $ItemID . '&ocr=f&pages=t&apikey=' . $config['api_key'] . '&format=json';
			
		echo $url . "\n";

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);
	$item_data = json_decode($json);
		
	// articles	from BioStor	
	$filename = $config['cache'] . '/' . $ItemID . '-articles.json';

	if (!file_exists($filename) || $force)
	{
		$url = 'http://localhost/biostor-classic/www/itemarticles.php?item=' . $ItemID;

		$json = get($url);
		
		file_put_contents($filename, $json);
	}

	// get data
	$json = file_get_contents($filename);
	$item_articles = json_decode($json);
	

	// set colours for articles
	
	// default colour
	$page_colours = array();
	foreach ($item_data->Result->Pages as $page)
	{
		$page_colours[$page->PageID] = $config['no_colour']; 
	}
	
	$page_to_biostor = array();
	
	// Colour in any articles using one of a set of colours,
	// we cycle through these if there are more articles than colours
	$colour_index = 0;
	
	if (isset($item_articles->articles))
	{
		foreach ($item_articles->articles as $article)
		{
			$colour = $config['colours'][$colour_index];
		
			foreach ($article->bhl_pages as $PageID)
			{
				$page_colours[$PageID] = $colour;
			
				$page_to_biostor[$PageID] = $article->reference_id;
			}	
	
			$colour_index++;
			if ($colour_index == count($config['colours']))
			{
				$colour_index = 0;
			}
		}
	}
		
	// OK at this point we have the item ready to display so we generate HTML view
	// with page thumbnails, and a thumbnail for whole item
	
	$thing = new stdclass;	
	$thing->things = array();
	
	$thing->name = $item_data->Result->Volume;
	$thing->id = $item_data->Result->ItemID;
	$thing->parentId = $item_data->Result->PrimaryTitleID;
	$thing->description = "Identified parts in this item are shown in colour.";
	
	foreach ($item_data->Result->Pages as $page)
	{
		$obj = new stdclass;
		
		// label for page
		if (isset($page->PageNumbers) && count($page->PageNumbers) > 0)
		{
			$obj->name = trim(str_replace('%', ' ', $page->PageNumbers[0]->Prefix . ' ' . $page->PageNumbers[0]->Number));
		}
		
		// links
		$obj->imageUrl = 'https://aipbvczbup.cloudimg.io/s/height/100/http://biodiversitylibrary.org/pagethumb/' . $page->PageID . ',200,200';
		$obj->imageUrl = 'https://aezjkodskr.cloudimg.io/http://biodiversitylibrary.org/pagethumb/' . $page->PageID . ',200,200?height=100';

		$obj->imageUrl = 'http://biodiversitylibrary.org/pagethumb/' . $page->PageID . ',200,200?height=100';

		$obj->url = 'https://biodiversitylibrary.org/page/' . $page->PageID;
		
		if (isset($page_to_biostor[$page->PageID]))
		{
			$obj->biostor = $page_to_biostor[$page->PageID];
		}
		
		// colour
		$obj->colour = $page_colours[$page->PageID];
		
		$thing->things[] = $obj;
	
	}
	
	$html = draw_things_html($thing, $config['width'], $config['height'], true);
	
	//print_r($thing);
	
	$dir = dirname(__FILE__)  . "/" . $thing->parentId;
	if (!file_exists($dir))
	{
		$oldumask = umask(0); 
		mkdir($dir, 0777);
		umask($oldumask);
	}
	
	$base_filename = $dir . '/' . $thing->id;
	
	$html_filename = $base_filename  . '.html';
	file_put_contents($html_filename, $html);
	
	$svg = draw_things_svg($thing, $config['width'], $config['height']);

	$svg_filename = $base_filename  . '.svg';
	file_put_contents($svg_filename, $svg);
	
	$png_filename = $base_filename  . '.png';

	
	// convert SVG to bitmap image
	$command = "convert $svg_filename -transparent white -resize 64x64 $png_filename";
	
	system($command);


}


//----------------------------------------------------------------------------------------
// title
function get_title($TitleID, $force = false)
{
	global $config;
	
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
	
	//print_r($title_data);
	
	$items = array();
	
	foreach ($title_data->Result->Items as $item)
	{
		$items[] = $item->ItemID;
	}
	
	foreach ($items as $item)
	{
		if (file_exists($TitleID . '/' . $item . '.html') && !$force)
		{
			echo "Already done item $item...\n";	
		}
		else
		{	
			echo "  Getting item $item...\n";
			get_item($item, $force);
		}
	}	
		
	$thing = new stdclass;
	$thing->things = array();

	$thing->id = $TitleID;		
	$thing->name = $title_data->Result->FullTitle;
	$thing->back = ".";
	
	
	foreach ($title_data->Result->Items as $item)
	{
		$obj = new stdclass;
		
		$obj->id 	= $item->ItemID;
		$obj->name 	= $item->Volume;
				
		// links
		$obj->imageUrl 	= $TitleID . '/' . $item->ItemID . '.png';
		$obj->url		= $TitleID . '/' . $item->ItemID . '.html';
		
		$thing->things[] = $obj;
	
	}
	
	//print_r($thing);
	
	$html = draw_things_html($thing, $config['width'], $config['height']);
	
	$html_filename = $thing->id  . '.html';
	file_put_contents($html_filename, $html);
	
	// thumbnail for whole title
	
	$svg = draw_things_svg_thumbnail($thing, $config['width'], $config['height']);
	
	$svg_filename = $thing->id  . '.svg';
	file_put_contents($svg_filename, $svg);
	
	$png_filename = $thing->id  . '.png';	
	
	// convert SVG to bitmap image
	$command = "convert $svg_filename -transparent white -resize 64x64 $png_filename";
	
	system($command);

}

//----------------------------------------------------------------------------------------

//get_item(148431, true);

// titles from array
if (0)
	{
	$titles = array(
	144396, // Beagle
	77508, // Journal of the Royal Society of Western Australia
	61449, // Memoirs of the Queensland Museum
	128759, // Nuytsia: journal of the Western Australian Herbarium
	142573, // Northern Territory Naturalist
	14019, // Proceedings of the Royal Society of Queensland
	61893, // Records of the South Australian Museum
	144635, // Records of the Queen Victoria Museum Launceston
	125400, // Records of The Western Australian Museum
	16197, // Transactions and proceedings and report of the Royal Society of South Australia
	168316, // Transactions of The Royal Society of South Australia
	43746, // The Victorian Naturalist
	);

	$titles = array(
	144396, // Beagle
	77508, // Journal of the Royal Society of Western Australia
	//125400, // Records of The Western Australian Museum
	144635, // Records of the Queen Victoria Museum Launceston
	61449, // Memoirs of the Queensland Museum
	128759, // Nuytsia: journal of the Western Australian Herbarium
	142573, // Northern Territory Naturalist
	14019, // Proceedings of the Royal Society of Queensland
	61893, // Records of the South Australian Museum
	144635, // Records of the Queen Victoria Museum Launceston
	125400, // Records of The Western Australian Museum
	16197, // Transactions and proceedings and report of the Royal Society of South Australia
	168316, // Transactions of The Royal Society of South Australia
	43746, // The Victorian Naturalist

	);
	
	$titles = array(
		174750, // Cryptogamie. Algologie
	);
		


}

// titles from file
if (1)
{

	$filename = "titles.tsv";

	$filename = "mnhn.tsv";
	$filename = "redo.tsv";


	// read from file list
	$headings = array();

	$row_count = 0;


	$titles = array();

	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$line = trim(fgets($file_handle));
		
		$row = explode("\t",$line);
	
		$go = is_array($row) && count($row) > 1;
	
		if ($go)
		{
			if ($row_count == 0)
			{
				$headings = $row;		
			}
			else
			{
				$obj = new stdclass;
		
				foreach ($row as $k => $v)
				{
					if ($v != '')
					{
						$obj->{$headings[$k]} = $v;
					}
				}
		
				print_r($obj);	
			
				$titles[]  = $obj;
			}
		}	
		$row_count++;	
	
	}	

	print_r($titles);

}

//exit();


$force = false;
$force = true;

foreach ($titles as $title)
{
	$TitleID = $title->TitleID;
	
	
	if (file_exists($TitleID . '.html') && !$force)
	{
		echo "Already done title $TitleID...\n";	
		echo "Updating title $TitleID...\n";
		get_title($TitleID, $force);		
	}
	else
	{
		echo "Doing title $TitleID...\n";
		get_title($TitleID, $force);
	}
	
	// get_title($TitleID, $force);
}

$html = '';
foreach ($titles as $title)
{
	$html .= '<tr>';
	$html .= '<td>' . '<a href="' . $title->TitleID . '.html"><img src="' . $title->TitleID . '.png"></a>' . '</td>';
	$html .= '<td>' . $title->FullTitle . '</td>';
	$html .= '</tr>';
	$html .= "\n";

}

file_put_contents('extra.html', $html);



?>