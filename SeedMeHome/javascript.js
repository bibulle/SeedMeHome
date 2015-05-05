var movies_sort = true;

//--------------------------
// Manage the torrent upload
//--------------------------
function onuploadtorrentFormSubmit(e) {
	//do some checks?
	$('result').update('uploading');
}
function onuploadtorrentReady(result) {
	$('result').update(result);
	$('result_div').setStyle({
		top: '100px'
	});
	Effect.toggle('result_div', 'appear', { duration: 0.2 });
}

//--------------------------
// Get and display status
//--------------------------
function refreshStatus() {
	//TODO :  get interval from settings.php
	getStatus();
	setTimeout('refreshStatus()',10000); // refresh every 10 second
}
function getStatus() {
	//first send status request to backend
       var options = {
                    onSuccess : getStatusSuccess
        };
        new Ajax.Request('get.php?do=getStatus', options);
}
function getStatusSuccess(transport) {
    //After retreiving status, update screen
    var json = transport.responseJSON;

    if (json==undefined) {
    	$('status1').update('Error');
    	$('status2').update('');
		return;
    }

    // Get the user role
    var user_role = json.user_role;
    
    //create status div
    var status = '';
	status += json.eta+'<br>';	

	// Free disk space
	status += 'Free disk space : <br>';	
	json.freedisk.each ( function (disk) {
		if (!disk.size) {
			status += '&nbsp;&nbsp;&nbsp;&nbsp; '+disk.name+' : ??? <br>';
		} else {
			status += '&nbsp;&nbsp;&nbsp;&nbsp; '+disk.name+' : '+size_format(disk.size)+' <br>';
		}
	});
	$('status1').update(status);

	// Seedbox info
	if (json.server_info) {
      status = '';
	  status += 'Server info : <br>';	
	  status += '&nbsp;&nbsp;&nbsp;&nbsp; donwload : '+size_format(json.server_info.downloadSpeed)+'/s <br>';	
	  status += '&nbsp;&nbsp;&nbsp;&nbsp; upload : '+size_format(json.server_info.uploadSpeed)+'/s <br>';	
	  $('status2').update(status);
	}
	
}

//--------------------------
//Get and display seed queue
//--------------------------
function refreshSeedQueue() {
	getSeedQueue();
	setTimeout('refreshSeedQueue()',10000); // refresh every 10 second
}
function getSeedQueue() {
	//first send status request to backend
       var options = {
                    onSuccess : getSeedQueueSuccess
        };
        new Ajax.Request('get.php?do=getSeedQueue', options);
}
function getSeedQueueSuccess(transport) {
    //After retreiving status, update screen
    var json = transport.responseJSON;

    if (json==undefined) {
    	$('queue').update('Error');
		return;
    }

    // Get the user role
    var user_role = json.user_role;
    
	// SeedBox torrent lists
	if (json.server_torrents) {
	  //create current dowloading and queue div
	  var queue = '<ul id="queuelist">';
	  var cpt_queue = 0;
	  json.server_torrents.torrents.each ( function (line) {
	    cpt_queue = cpt_queue + 1;
	    st = line.status;
	    if (st == 8) {
		    st = '<div style="float:left;width:12px" title="Upload">U</div>';
		    but1="pause";
	    } else if (st == 16) {
		    st = '<div style="float:left;width:12px" title="Pause">P</div>';
		    but1="restart";
	    } else if (st == 4) {
		    st = '<div style="float:left;width:12px" title="Download">D</div>';
		    but1="pause";
	    } else {
	    	st = '<div style="float:left;width:12px" title="???">'+st+'</div>'
		    but1="pause";
	    }
	    if (line.percentDone != 1) {
		    completed = number_format(line.percentDone*100,0);
	    } else {
		    completed = number_format((line.ftpSizeDone/line.totalSize)*100,0);
	    }
        queue += '<li id="id_'+line.id+'"><div class="queue_item">';
        if (user_role == 'admin') {
		  queue += '<div class="button1 green_'+but1+'" onclick="javascript:'+but1+'_queue('+"'"+line.id+"'"+');">'+but1+'</div>';
		  queue += '<div class="button2 red" onclick="javascript:remove_queue('+"'"+line.id+"'"+');">remove</div>';
        }
		queue += '<div><div style="float:left;width:16px;font-size:0.7em">'+cpt_queue+" </div>"+line.name+'</div>';
		queue += '<div class="progress">';
		queue +=   st;
		queue += ' <div class="bar-border">';
		queue += '  <div class="bar" style=\'width: '+(line.percentDone*100)+'px;\'>';
		queue += '    <div class="bar2" style=\'width: '+((line.ftpSizeDone/line.totalSize)*100)+'px;\'></div>';
		queue += '  </div>';
		queue += ' </div>';
		queue += ' <div style="width:500px">'+completed+'% complete, '+size_format(line.totalSize);
		queue += ' (<span class=\'rateDownload rateDownload_'+line.rateDownload+'\'>'+size_format(line.rateDownload)+'/s</span>-';
		queue += '<span class=\'rateUpload rateUpload_'+line.rateUpload+'\'>'+size_format(line.rateUpload)+'/s</span>-'+number_format(line.uploadRatio,1)+')</div>';
		queue += '</div></div></li>';
      });
      queue += '</ul>';
      $('queue').update(queue);
	  Sortable.create('queuelist', {
                containment: ['queuelist'],
                onUpdate: function() {
                        queueupdate ()
                }
	  });
	} else {
		$('queue').update('');
	}
}

//--------------------------
//Get and display log files
//--------------------------
function refreshLog() {
	getLog();
	setTimeout('refreshLog()',10000); // refresh every 10 second
}
function getLog() {
	//first send status request to backend
       var options = {
                    onSuccess : getLogSuccess
        };
        new Ajax.Request('get.php?do=getLog', options);
}
function getLogSuccess(transport) {
    //After retreiving status, update screen
    var json = transport.responseJSON;

    if (json==undefined) {
    	$('log').update('Error');
		return;
    }

    // Get the user role
    var user_role = json.user_role;
    
	// Reading logs
	var log = '';
	json.log_entries.each ( function (line) {
		log += line+'<br>';
	});
	$('log').update(log);

}

//--------------------------
//Get and display downloaded files, movies and tvshows
//--------------------------
function refreshDoneFiles() {
	getDoneFiles();
	setTimeout('refreshDoneFiles()',10000); // refresh every 10 second
}
function refreshMovies() {
	getMovies();
	setTimeout('refreshMovies()',10000); // refresh every 10 second
}
function refreshTvShows() {
	getTvShows();
	setTimeout('refreshTvShows()',10000); // refresh every 10 second
}
function getFiles(target) {
	switch (target) {
	case 'done':
		getDoneFiles();
		break;
	case 'movies':
		getMovies();
		break;
	case 'tvshows':
		getTvShows();
		break;

	default:
		break;
	}
}
function getDoneFiles() {
	//first send status request to backend
       var options = {
                    onSuccess : function(transport) { getFileSuccess(transport, 'done');}
        };
        new Ajax.Request('get.php?do=getDoneFiles', options);
}
function getMovies() {
	//first send status request to backend
       var options = {
                    onSuccess : function(transport) { getFileSuccess(transport, 'movies');}
        };
        new Ajax.Request('get.php?do=getMovies&movies_sort='+movies_sort, options);
}
function getTvShows() {
	//first send status request to backend
       var options = {
                    onSuccess : function(transport) { getFileSuccess(transport, 'tvshows');}
        };
        new Ajax.Request('get.php?do=getTvShows', options);
}
function getFileSuccess(transport, cible) {
    //After retreiving status, update screen
    var json = transport.responseJSON;

    if (json==undefined) {
    	$(cible).update('Error');
		return;
    }

    // Get the user role
    var user_role = json.user_role;
    
	// Clean
   	$(cible).childElements().each(function(e) { 
		e.remove(); 
    });
   	$(cible).update='';
	var cpt = 0;
   	
	if (json.files) {
	  var father = '';
	  var cpt_poll = 0;
	  json.files.each ( function (line) {
		if (father=='') {
			father = line.name;
		} else if (line.index == 1) {
			father = line.name;
		}
		var id=cible+cpt;
		var classname = line.type+" "+line.type+line.index;
		if (cpt % 2 == 0) {
			classname += " odd";
		}
		if (line.temp == 'yes') {
			classname += " temp";
		}
		
		file_div = document.createElement('div');
		file_div.className = classname;
		file_div.setAttribute('id', id);
		//file_div.innerText = line.name;

		if ((line.editable == 'yes') && (line.temp != 'yes')) {
			butt_remove = document.createElement('div');
			butt_remove.className = 'button_r red';
			butt_remove.setAttribute('id', id+'_butt_remove');
			butt_remove.innerHTML = 'Remove';
			file_div.appendChild(butt_remove);
			Event.observe(butt_remove , 'click', function(event) {Event.stop(event);removeFile(line.source_url, cible);});

			butt_move = document.createElement('div');
			butt_move.className = 'button_r green';
			butt_move.setAttribute('id', id+'_butt_move');
			butt_move.innerHTML = 'Move';
			file_div.appendChild(butt_move);
			Event.observe(butt_move , 'click', function(event) {Event.stop(event);moveFile(line.source_url, line.target_url, event);});
		}
		
		if ((line.allpoll !== undefined) && (line.temp != 'yes')) {
			butt_allpoll = document.createElement('div');
			butt_allpoll.className = 'allpoll allpoll'+line.allpoll;
			butt_allpoll.setAttribute('id', id+'_butt_move');
			butt_allpoll.innerHTML = '&nbsp;';
			file_div.appendChild(butt_allpoll);
			if (line.allpoll == 0) {
				butt_allpoll.setAttribute('title', "No vote");
			} else if (line.allpoll == 1) {
				butt_allpoll.setAttribute('title', "1 vote");
			} else {
				butt_allpoll.setAttribute('title', line.allpoll+" votes");
			}

		}
		if ((line.mypoll !== undefined) && (line.temp != 'yes')) {
			butt_mypoll = document.createElement('div');
			butt_mypoll.className = 'mypoll mypoll'+line.mypoll;
			butt_mypoll.setAttribute('id', id+'_butt_move');
			butt_mypoll.setAttribute('title', "Vote !!");
			butt_mypoll.innerHTML = '&nbsp;';
			file_div.appendChild(butt_mypoll);
			cpt_poll += line.mypoll;
			Event.observe(butt_mypoll , 'click', function(event) {
				Event.stop(event);
				if (line.mypoll == 0) {
					if (cpt_poll >= 5) {
						alert("Limited to 5 poll !!")
					} else {
						this.className = 'mypoll mypoll1';
						pollFile(line.name, line.mypoll, event);
					}
				} else {
					cpt_poll -= 1;
					this.className = 'mypoll mypoll0';
					pollFile(line.name, line.mypoll, event);
				}			
			});
			
		}
		
		if (line.dateS !== undefined) {
			file_div_date = document.createElement('div');
			file_div_date.className = 'file_date';
			file_div_date.innerHTML = line.dateS;
			file_div.appendChild(file_div_date);
			Event.observe(file_div_date , 'click', function(event) {Event.stop(event);movies_sort=!movies_sort;getMovies();});
    }
    		
		file_div_name = document.createElement('div');
		//file_div_name.className = 'button_r red';
		file_div_name.innerHTML = line.name;
		file_div.appendChild(file_div_name);

		$(cible).appendChild(file_div);

	    var father1= father;
	    Event.observe(file_div , 'click', function(event) {displayInfo(father1, event);});

	    cpt++;
	  });
	}
	
}

//--------------------------
// manage SeedBox queue
//--------------------------
function remove_queue (id) {
    //pause or resume download first, onsuccess refres screen
    new Ajax.Request("set.php?do=remove&id="+id, {
                                onSuccess : getSeedQueue
    });
}
function pause_queue (id) {
    //pause or resume download first, onsuccess refres screen
    new Ajax.Request("set.php?do=pause&id="+id, {
                                onSuccess : getSeedQueue
    });
}
function restart_queue (id) {
    //pause or resume download first, onsuccess refres screen
    new Ajax.Request("set.php?do=restart&id="+id, {
                                onSuccess : getSeedQueue
    });
}
	
//--------------------------
// Remove a file from downloaded file or movies or tvShows
//--------------------------
function removeFile (fileName, target) {
	//remove the file or directory
	new Ajax.Request("set.php?do=removeFile&filename="+fileName, {
                                onSuccess : function() {getFiles(target);}
        });
}

//--------------------------
//Move a file from downloaded file or movies or tvShows
//--------------------------
function moveFile (srcfileName, trgfileName, e) {
  //alert(srcfileName);
	//var sf = escape(unescape(srcfileName));
  //alert(sf);
	//sf = sf.replace(/[+]/g, ' ');
  //alert(sf);
	$('filename').value = srcfileName;     

	var tf = unescape(trgfileName);
	tf = tf.replace(/[+]/g, ' ');
	tf = tf.replace(/^.*[\/\\]/g,'');
	tf = tf.replace(/[.]/g,'+');
	tf = tf.replace(/[+]([^+]*)$/g,'.$1');
	tf = tf.replace(/[+]/g,' ');
	$('targetname').value = tf;

	$('movefile_div').style.top = (e.pageY+10)+"px";
	$('movefile_div').style.left = (e.pageX-200)+"px";
	Effect.toggle('movefile_div', 'appear', { duration: 0.2, afterFinish: function(){$('targetname').focus();} });
	
}
//--------------------------
// Poll on a file
//--------------------------
function pollFile (fileName, state, target, e) {
	//remove the file or directory
	new Ajax.Request("set.php?do=pollFile&filename="+fileName+"&state="+state, {
                                onSuccess : function() {getFiles(target);}
        });
}

//--------------------------
// Display file info from imdb (launch)
//--------------------------
var working= false;
function displayInfo(file, event) {
	  if (working) {
		  return;
	  }
	working = true;
	var name = file;
	var tab=name.match(/(.*)[.][^.]*$/);
	if (tab) {
	  name = tab[1];
	}
	name = name.replace(/[.]/g, ' ');

	if ($('detail_legend').innerHTML != name) {
		$('detail_legend').innerHTML = name;
 
  	  	$('detail').innerHTML = "<div>loading...</div>";
		var position=Event.pointerY(event)-80;
		if (position < $('column1_top').cumulativeOffset().top) {
			position = $('column1_top').cumulativeOffset().top;
		}
		if (position+$('column1_detail').getHeight()+5 > $('column2_top').cumulativeOffset().top) {
			position = $('column2_top').cumulativeOffset().top-$('column1_detail').getHeight()-5;
		}

		if ($('column1_detail').style.display == 'none') {
			new Effect.Move('column1_detail', {x:0, y:position , mode: 'absolute' ,duration:0, queue: 'end'});
			new Effect.toggle('column1_detail', 'appear', { duration: 0.2 , queue: 'end'});
		} else {
			new Effect.Move('column1_detail', {x:0, y:position , mode: 'absolute' ,duration:0.2});
		}

		//Launch the Ajax Sucess
		var options = {
				onSuccess : displayInfoSucces,
				onFailure : function() {working= false;}
     	};
     	new Ajax.Request('getImdb.php?title='+escape(name), options);
	} else {
		working = false;
	}
	

 
}
//--------------------------
// Display file info from imdb (launch)
//--------------------------
function displayInfoSucces(transport) {
	//After retreiving the movie, update screen
  var json = transport.responseJSON;

  if (json==undefined) {
  	$('detail').update('Movie not found');
		working = false;
		return;
  }
  if (json.initial_title == $('detail_legend').innerHTML) {

  	$('detail').childElements().each(function(e) { 
          e.remove(); 
      });
  	$('detail').replace='';
	    
  	detail_img_div = document.createElement('div');
  	detail_img_div.className = 'detail_img_div';
  	$('detail').appendChild(detail_img_div);

  	if (json.imageUrl) {
  	  detail_img_wait = document.createElement('img');
	      detail_img_wait.setAttribute('src', 'img/loading1.gif');
	      detail_img_wait.setAttribute('id', 'detail_img_wait');
	      detail_img_div.appendChild(detail_img_wait);

  	  detail_img = document.createElement('img');
  	  detail_img.setAttribute('src', json.imageUrl);
  	  detail_img.setAttribute('id', 'detail_img');
  	  detail_img.setAttribute('style', 'display:none');
  	  detail_img_div.appendChild(detail_img);
  	}
  	
  	detail_right = document.createElement('div');
  	detail_right.className = 'detail_right';
  	$('detail').appendChild(detail_right);

  	if (json.certificate) {
  		json.certificate = json.certificate.replace('/', '_');
  		movie_certif = document.createElement('div');
  		movie_certif.className = 'movie_certif movie_certif'+json.certificate;
  		//movie_certif.update(json.certificate);
	    	detail_right.appendChild(movie_certif);
  	}

  	movie_year = document.createElement('div');
  	movie_year.className = 'movie_year';
  	movie_year.innerHTML = json.year;
  	detail_right.appendChild(movie_year);

  	movie_title = document.createElement('div');
  	movie_title.className = 'movie_title';
  	movie_title.innerHTML = json.title;
  	detail_right.appendChild(movie_title);

  	if (json.genres) {
  	  movie_genres = document.createElement('div');
  	  movie_genres.className = 'movie_genres';
	      detail_right.appendChild(movie_genres);
  	  for(i=0; i<json.genres.length; i++) { 
  		movie_genre = document.createElement('img');
  		movie_genre.className = 'movie_genre movie_genre'+removeAccents(json.genres[i]);
  		movie_genre.src = 'img/pix.gif';
  		movie_genre.title = json.genres[i];
  		movie_genres.appendChild(movie_genre);
  	  }
  	  
  	}

  	movie_plot = document.createElement('div');
  	movie_plot.className = 'movie_plot';
  	movie_plot.innerHTML = json.plot;
  	$('detail').appendChild(movie_plot);

  	
  	if (json.imageUrl) {
  	  Event.observe(detail_img, 'load', function(event) {
  	    Effect.Fade('detail_img_wait', { duration: 0.0, queue: 'end' });
  	    Effect.Appear('detail_img', { duration: 0.2, queue: 'end' });
  	    Event.stopObserving('detail_img', 'load');
	      });
  	  Event.observe(detail_img, 'error', function(event) {
  	    $('detail_img').src = 'getImage.php?url='+escape(json.imageUrl);
  	    Event.stopObserving('detail_img', 'error');
	    	Event.observe(detail_img, 'error', function(event) {
	    	  $('detail_img_wait').src = 'skull.png';
	    	  Event.stopObserving('detail_img', 'error');
	    	});
  	  });
  	}

	working = false;
  	
  }
}

//--------------------------
//Format file size
//--------------------------
function size_format (filesize) {

	if (filesize >= 1073741824) {
	     filesize = number_format(filesize / 1073741824, 2, '.', '') + ' Gb';
	} else { 
		if (filesize >= 1048576) {
     		filesize = number_format(filesize / 1048576, 2, '.', '') + ' Mb';
   	} else { 
			if (filesize >= 1024) {
    		filesize = number_format(filesize / 1024, 0) + ' Kb';
  		} else {
    		filesize = number_format(filesize, 0) + ' b';
			};
 		};
	};
  return filesize;
};

//--------------------------
// Format numbers
//--------------------------
function number_format( number, decimals, dec_point, thousands_sep ) {
    // http://kevin.vanzonneveld.net
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     bugfix by: Michael White (http://crestidg.com)
    // +     bugfix by: Benjamin Lupton
    // +     bugfix by: Allan Jensen (http://www.winternet.no)
    // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)    
    // *     example 1: number_format(1234.5678, 2, '.', '');
    // *     returns 1: 1234.57     
 
    var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
    var d = dec_point == undefined ? "," : dec_point;
    var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
    var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
 
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

//--------------------------
// Format date
//--------------------------
function date_format(milisec) {
	date = new Date();
	date.setTime(milisec*1000);

	var d  = date.getDate();
	var day = (d < 10) ? '0' + d : d;
	var m = date.getMonth() + 1;
	var month = (m < 10) ? '0' + m : m;
	var yy = date.getYear();
	var year = (yy < 1000) ? yy + 1900 : yy;

	var h = date.getHours();
	var hours = (h < 10) ? '0' + h : h;
	var m = date.getMinutes();
	var minutes = (m < 10) ? '0' + m : m;
	var s = date.getSeconds();
	var seconds = (s < 10) ? '0' + s : s;
	
	return day + "/" + month + "/" + year + " " + hours + ":" + minutes + ":"+ seconds;
}
//--------------------------
// Remove accent from a string
//--------------------------
function removeAccents(strAccents){
    strAccents = strAccents.split('');
    strAccentsOut = new Array();
    strAccentsLen = strAccents.length;
    var accents = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž';
    var accentsOut = ['A','A','A','A','A','A','a','a','a','a','a','a','O','O','O','O','O','O','O','o','o','o','o','o','o','E','E','E','E','e','e','e','e','e','C','c','D','I','I','I','I','i','i','i','i','U','U','U','U','u','u','u','u','N','n','S','s','Y','y','y','Z','z'];
    for (var y = 0; y < strAccentsLen; y++) {
        if (accents.indexOf(strAccents[y]) != -1) {
            strAccentsOut[y] = accentsOut[accents.indexOf(strAccents[y])];
        }
        else
            strAccentsOut[y] = strAccents[y];
    }
    strAccentsOut = strAccentsOut.join('');
    return strAccentsOut;
}
