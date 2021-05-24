<?php

// Get artciles that overlap (and hence may be duplicates)

$config['cache']   = dirname(__FILE__) . '/cache';


$TitleID = 2202;

$filename = $config['cache'] . '/title-' . $TitleID . '.json';


$json = file_get_contents($filename);

$title_data = json_decode($json);

$items = array();

$duplicates = array();

$dois = array();

foreach ($title_data->Result->Items as $item)
{
		
	// articles	from BioStor	
	$filename = $config['cache'] . '/' . $item->ItemID. '-articles.json';
	// get data
	$json = file_get_contents($filename);
	$item_articles = json_decode($json);
	
	print_r($item_articles);
	
	
	$pages = array();
	
	foreach ($item_articles->articles as $article)
	{
		if (isset($article->doi))
		{
			$dois[$article->reference_id] = $article->doi;
		}
	
		foreach ($article->bhl_pages as $PageID)
		{
			if (!isset($pages[$PageID]))
			{
				$pages[$PageID] = array();
			}
			$pages[$PageID][] = (Integer)$article->reference_id;
		}
	}
	
	print_r($pages);
	
	foreach ($pages as $page)
	{
		if (count($page) == 2)
		{
			asort($page, SORT_NUMERIC);
			$duplicates[$page[0]] = $page[1];
		}
	}
	
}

print_r($dois);

foreach ($duplicates as $k => $v)
{
	echo $k . ' = ' . $v;
	
	if (isset($dois[$k]) && isset($dois[$v]))
	{
		echo " **";
	}
	
	echo  "\n";

}

//print_r($duplicates);


?>