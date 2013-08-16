function calendar_commands(){this.init=function(){rcmail.register_command("plugin.calendar_newevent",calendar_commands.newevent,!0);rcmail.register_command("plugin.calendar_reload",calendar_commands.reload,!0);rcmail.register_command("plugin.calendar_switchCalendar",calendar_commands.switchCalendar,!0);rcmail.register_command("plugin.exportEventsZip",calendar_commands.exportEventsZip,!0);rcmail.register_command("plugin.importEvents",calendar_commands.importEvents,!0);rcmail.register_command("plugin.calendar_print",
calendar_commands.printSubmenu,!0);rcmail.register_command("plugin.events_print",calendar_commands.previewPrintEvents,!0);rcmail.register_command("plugin.tasks_print",calendar_commands.previewPrintTasks,!0);rcmail.register_command("plugin.calendar_filterEvents",calendar_commands.filterEvents,!0);rcmail.register_command("plugin.calendar_do_print",calendar_commands.printEvents,!0);rcmail.register_command("plugin.calendar_toggle_view",calendar_commands.toggleView,!0);this.scheduleSync()};this.ksort=
function(b){var f=[],g={},c;for(c in b)f.push(c);f.sort();for(c in f)g[f[c]]=b[f[c]];return g};this.newevent=function(){d=36E5*Math.floor((new Date).getTime()/1E3/3600*rcmail.env.calsettings.settings.timeslots)/rcmail.env.calsettings.settings.timeslots+36E5/rcmail.env.calsettings.settings.timeslots;calendar_callbacks.dayClick(new Date(d),!1,!0,!1,rcmail.env.calsettings)};this.compose=function(b){b+="&_id="+rcmail.env.edit_event.id;rcmail.env.compose_newwindow?opencomposewindowcaller(b):rcmail.env.compose_extwin?
rcmail.open_compose_step(b):document.location.href=b};this.edit_event=function(b,f){rcmail.env.edit_recurring=b;"resize"==f?calendar_callbacks.eventResize(rcmail.env.edit_event,rcmail.env.edit_delta,rcmail.env.calsettings):"move"==f?calendar_callbacks.eventDrop(rcmail.env.edit_event,rcmail.env.edit_dayDelta,rcmail.env.minuteDelta,rcmail.env.allDay,rcmail.env.revertFunc,rcmail.env.calsettings):calendar_callbacks.eventClick(rcmail.env.edit_event,rcmail.env.calsettings);$("#calendaroverlay").hide();
$("#calendaroverlay").html("")};this.edit_recurring_html=function(b,f){var g;g="<div id='recurringdialog'><br /><fieldset><legend>"+rcmail.gettext("calendar.applyrecurring")+"</legend><p>";f&&(g=g+"<input class='button' type='button' onclick='calendar_commands.edit_event(\"initial\",\""+b+"\")' value='&bull;' />&nbsp;<span>"+rcmail.gettext("calendar.editall")+"</span><br />");g=g+"<input class='button' type='button' onclick='calendar_commands.edit_event(\"future\",\""+b+"\")' value='&bull;' />&nbsp;<span>"+
rcmail.gettext("calendar.editfuture")+"</span><br />";g=g+"<input class='button' type='button' onclick='calendar_commands.edit_event(\"single\",\""+b+"\")' value='&bull;' />&nbsp;<span>"+rcmail.gettext("calendar.editsingle")+"</span>";g=g+'</p></fieldset><div style=\'float: right\'><a href=\'#\' onclick=\'$("#calendar").fullCalendar("refetchEvents");$("#calendaroverlay").html("");$("#calendaroverlay").hide()\'>'+rcmail.gettext("calendar.cancel")+"</a></div></div>";$("#calendaroverlay").html(g);$("#calendaroverlay").show()};
this.reload=function(){$("#calendaroverlay").hide();var b=$("#calendar").fullCalendar("getDate"),b=Math.round(b.getTime()/1E3,0);rcmail.http_request("plugin.calendar_renew","_date="+b)};this.scheduleSync=function(){window.setTimeout("calendar_commands.syncCalendar()",1E4)};this.syncCalendar=function(){rcmail.http_request("plugin.calendar_syncEvents");window.setTimeout("calendar_commands.syncCalendar()",6E4)};this.triggerSearch=function(){var b=$("#calsearchfilter").val();for($("#calsearchset").hide();-1<
b.indexOf("\\");)b=b.replace("\\","");$("#calsearchfilter").val(b);if(2<b.length&&b!=rcmail.env.cal_search_string){var f=DstDetect($("#calendar").fullCalendar("getDate"));f[0]||(f[0]=new Date(0));f[1]||(f[1]=new Date(0));end=new Date($("#calendar").fullCalendar("getDate").getTime()+31536E6);rcmail.env.cal_search_string=b;rcmail.env.replication_complete||rcmail.display_message(rcmail.gettext("calendar.replicationincomplete"),"notice");rcmail.http_post("plugin.calendar_searchEvents","_str="+b)}else $("#calsearchdialog").dialog("close")};
this.searchFields=function(b){""==b&&$("#cal_search_field_summary").attr("checked","checked");rcmail.env.cal_search_string="";rcmail.http_post("plugin.calendar_searchSet",b)};this.gotoDate=function(b,f){b=parseInt(b);try{var g=60*-((new Date).getTimezoneOffset()-(new Date(1E3*b)).getTimezoneOffset())}catch(c){g=0}g=new Date(1E3*(b+g));f?($("#rcmrow"+f).addClass("selected"),$("#rcmmatch"+f).removeClass("calsearchmatch"),$("#rcmmatch"+f).addClass("calsearchmatchselected"),rcmail.env.calsearch_id=f):
rcmail.env.calsearch_id=null;$("#jqdatepicker").datepicker("setDate",g);$("#calendar").fullCalendar("gotoDate",$.fullCalendar.parseDate(g))};this.switchCalendar=function(){if(rcmail.env.replication_complete){var b=$("#calswitch");b.find("select[name='_caluser']");b.find("input[name='_token']");var f=rcmail.gettext("submit","calendar"),g=rcmail.gettext("cancel","calendar"),c={};c[f]=function(){$("#calendaroverlay").show();b.dialog("close");$('#filters-table-content input[type="checkbox"]').prop("checked",
!1);$('#filters-table-content input[value="'+$("#_caluser").val()+'"]').prop("checked",!0);if(0==$("#_caluser").val())rcmail.http_post("plugin.calendar_setfilters","_showlayers=1");else{var c=$("#filters_form").serialize();rcmail.http_post("plugin.calendar_setfilters",c)}rcmail.env.cal_search_string=""};c[g]=function(){b.dialog("close")};b.dialog({modal:!1,title:rcmail.gettext("switch_calendar","calendar"),width:500,close:function(){b.dialog("destroy");b.hide()},buttons:c}).show()}else rcmail.display_message(rcmail.gettext("backgroundreplication",
"calendar"),"error")};this.exportEventsZip=function(){return!0};this.importEvents=function(){calendar_callbacks.dayClick(new Date,1,{start:new Date},"agendaWeek",rcmail.env.calsettings);for(var b=0;5>b;b++)calendar_gui.initTabs(2,b);$("#event").tabs("select",2);$("#event").tabs("disable",0);$("#event").dialog("option","title",rcmail.gettext("calendar.import"))};this.printSubmenu=function(){$("#printmenu").is(":visible")?$("#printmenu").hide():$("#printmenu").show()};this.previewPrintTasks=function(){$(".popupmenu").hide();
return(mycalpopup=window.open("./?_task=dummy&_action=plugin.calendar_print_tasks","Print","width=740,height=740,location=0,resizable=1,scrollbars=1"))?(mycalpopup.focus(),rcmail.env.calpopup=!0):!1};this.previewPrintEvents=function(){$(".popupmenu").hide();var b;b="./?_task=dummy&_action=plugin.calendar_print_events&_view="+escape($("#calendar").fullCalendar("getView").name.replace("agenda","basic"));b=b+"&_date="+$("#calendar").fullCalendar("getDate").getTime()/1E3;return(mycalpopup=window.open(b,
"Print","width=740,height=740,location=0,resizable=1,scrollbars=1"))?(mycalpopup.focus(),rcmail.env.calpopup=!0):!1};this.filterEvents=function(){$("#filterslink").trigger("click")};this.printEvents=function(){$("#toolbar").hide();self.print();$("#toolbar").show();return!0};this.toggleView=function(){"agendalist"==rcmail.env.calendar_print_curview?(rcmail.env.calendar_print_curview="calendar",$("#agendalist").hide(),$("#calendar").show(),$("#calendar").fullCalendar("render")):(calendar_commands.createAgendalist(),
$("#agendalist").show(),$("#calendar").hide(),rcmail.env.calendar_print_curview="agendalist")};this.createAgendalist=function(){var b=[],f=[],g=[],c=[];g.Sun=rcmail.env.calsettings.settings.days_short[0];g.Mon=rcmail.env.calsettings.settings.days_short[1];g.Tue=rcmail.env.calsettings.settings.days_short[2];g.Wed=rcmail.env.calsettings.settings.days_short[3];g.Thu=rcmail.env.calsettings.settings.days_short[4];g.Fri=rcmail.env.calsettings.settings.days_short[5];g.Sat=rcmail.env.calsettings.settings.days_short[6];
var e,j,l,k,m='<tr><th width="1%" class="day">'+rcmail.gettext("day","calendar")+"</th>",p="<tr>";myevents=$("#calendar").fullCalendar("clientEvents");for(var a in myevents)if(c[c.length]=myevents[a],myevents[a].end)for(clone=jQuery.extend({},myevents[a]);clone.start.getTime()<clone.end.getTime()-864E5;)e=new Date(clone.start.getFullYear(),clone.start.getMonth(),clone.start.getDate(),0,0,0),clone=jQuery.extend({},myevents[a]),clone.start=new Date(e.getTime()+864E5),1==clone.start.getHours()&&(clone.start=
new Date(clone.start.getTime()-36E5)),23==clone.start.getHours()&&(clone.start=new Date(clone.start.getTime()+36E5)),c[c.length]=clone;myevents=[];for(a in c)e="",c[a].title&&(e=c[a].title),myevents[c[a].start.getTime()+"-"+e+"-"+a]=c[a];var c=calendar_commands.ksort(myevents),q=$("#calendar").fullCalendar("getView").visStart.getTime(),r=$("#calendar").fullCalendar("getView").visEnd.getTime(),h=0;for(a in c)c[a].end||(c[a].end=c[a].start),e=c[a].className,c[a].classNameDisp&&(e=c[a].classNameDisp),
"string"==typeof e&&"undefined"==typeof b[e]&&(b[e]=!0,h++,f[h]=e==rcmail.gettext("default","calendar")?"":e);f.sort();for(a in f)e=f[a],""==e&&(e=rcmail.gettext("default","calendar")),m=m+'<th width="'+parseInt(100/f.length)+'%">'+e+"</th>";b=10*parseInt(700/f.length/10);rcmail.env.cal_print_cols=f.length;m+="</tr>";p="";cats=[];for(a in c)if(c[a]&&c[a].start&&c[a].start.getTime()>=q||c[a]&&c[a].end&&c[a].end.getTime()<=r)parseInt(c[a].start.getTime()/6E4),parseInt(c[a].end.getTime()/6E4),c[a].start.getDay()!=
c[a].end.getDay()&&parseInt((new Date(c[a].start.getFullYear(),c[a].start.getMonth(),c[a].start.getDate(),23,59,59)).getTime()/6E4),e=parseInt((new Date(c[a].start.getFullYear(),c[a].start.getMonth(),c[a].start.getDate(),0,0,0)).getTime()/6E4)+"-",e+=parseInt((new Date(c[a].start.getFullYear(),c[a].start.getMonth(),c[a].start.getDate(),23,59,59)).getTime()/6E4),cats[e]?cats[e][cats[e].length]=c[a]:(cats[e]=[],cats[e][0]=c[a]);c=-1;for(a in cats)if(!(cats[a][0]&&cats[a][0].start.getTime()<q||cats[a][0]&&
cats[a][0].start.getTime()>=r)){e=$.fullCalendar.parseDate(cats[a][0].start);j=$.fullCalendar.formatDate(e,"ddd");e=$.fullCalendar.formatDate(e,js_date_formats[rcmail.env.rc_date_format]);j='<tr><td nowrap width="1%" align="center" class="day">'+g[j]+"<br /><small>("+e+")</small></td>";var n={};for(h in cats[a])l=$.fullCalendar.formatDate(cats[a][h].start,js_time_formats[rcmail.env.rc_time_format]),cats[a][h].end?cats[a][h].start.getTime()==cats[a][h].end.getTime()?(k=l,l=""):0==cats[a][h].start.getHours()&&
0==cats[a][h].start.getMinutes()&&23==cats[a][h].end.getHours()&&59==cats[a][h].end.getMinutes()?(l="",k=rcmail.gettext("all-day","calendar")):k=$.fullCalendar.formatDate(cats[a][h].end,js_time_formats[rcmail.env.rc_time_format]):k="",e=cats[a][h].className,cats[a][h].classNameDisp&&(e=cats[a][h].classNameDisp),e==rcmail.gettext("default","calendar")&&(e=""),content=cats[a][h].title,cats[a][h].location&&(content=content+"<br />------<br /><small>@ "+cats[a][h].location+"</small>"),cats[a][h].description&&
(content=content+"<br />------<br /><small>"+cats[a][h].description+"</small>"),content+='<br />------<br /><table cellspacing="0" cellpadding="0">',""!=l&&(content=content+'<tr><td style="border-style:none"><small>'+rcmail.gettext("start","calendar")+':&nbsp;</small></td><td style="border-style:none"><small>'+l+"</small></td></tr>"),""!=k&&(content=k==rcmail.gettext("all-day","calendar")||""==l?content+'<tr><td style="border-style:none"><small>'+k+"</small></td></tr>":content+'<tr><td style="border-style:none"><small>'+
rcmail.gettext("end","calendar")+':&nbsp;</small></td><td style="border-style:none"><small>'+k+"</small></td></tr>"),content+="</table>",content='<fieldset style="max-width: '+b+50+'px;word-wrap: break-word;" class="print">'+content+"</fieldset>",n[e]=n[e]?n[e]+content:content;for(h in f)j=n[f[h]]?j+'<td valign="top">'+n[f[h]]+"</td>":j+"<td>&nbsp;</td>";c++;j+="</tr>";rcmail.env.myevents[c]=a+"_"+j}rcmail.env.myevents.sort();for(a in rcmail.env.myevents)e=rcmail.env.myevents[a].split("_"),j=rcmail.env.myevents[a].replace(e[0]+
"_",""),p+=j;m='<tr><th colspan="'+f.length+3+'">'+$.fullCalendar.formatDate(new Date(q),js_date_formats[rcmail.env.rc_date_format])+" - "+$.fullCalendar.formatDate(new Date(r-1),js_date_formats[rcmail.env.rc_date_format])+"</th></tr>"+m;$("#agendalist").html('<table id="calprinttable" cellspacing="0" width="99%"><thead>'+m+"</thead><tbody>"+p+"</tbody></table>")}}calendar_commands=new calendar_commands;