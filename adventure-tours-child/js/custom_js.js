function initTourSearchForm(){
	(function($){
		var f=null;
		for(var i=0;i<document.forms.length;++i){
			if(document.forms[i].querySelector('input[name=toursearch]') && document.forms[i].querySelector('select[name="tourtax[pa_kontinent]"]') && document.forms[i].querySelector('select[name="tourtax[pa_land]"]')){
				f=document.forms[i];
				break;
			}
		}
		if(!f) return false;
		$(f['tourtax[pa_kontinent]']).on('change',(function(f){ return function(){
			$.ajax({
				url: window.location,
				method: "POST",
				data: { "get_tour_countries": this.value},
			}).done((function(f){ return function(res){
				f['tourtax[pa_land]'].innerHTML=res;
				$(f['tourtax[pa_land]']).selectpicker('refresh');
			}})(f));
		}})(f));
	})(jQuery);
}
function initTourTabs(){
	var tabs=document.querySelectorAll('main > .tours-tabs .nav-tabs li a');
	$=jQuery;
	for(var i=0;i<tabs.length;++i){
		if(tabs[i].getAttribute('href')=='#tabatab0' && tabs[i].getAttribute('aria-expanded')=='true'){
			$('#tours_adventure_tours-2').hide();
			$('.product_right_gallery').show();
		}else if(tabs[i].getAttribute('href')=='#tabatab0'){			
			$('#tours_adventure_tours-2').show();
			$('.product_right_gallery').hide();
		}
		tabs[i].addEventListener('click',function(){
			if(this.getAttribute('href')=='#tabatab0'){
				$('#tours_adventure_tours-2').hide();
				$('.product_right_gallery').show();
			}else{			
				$('#tours_adventure_tours-2').show();
				$('.product_right_gallery').hide();
			}
		});
	}
}
window.addEventListener('load',function(){
	initTourSearchForm();
	initTourTabs();
});
/*
 * Get booking date, adult and child quantity from Booking form
 * and fill in Pop-up Enquiry form.
 */
function initPopUpFormValues(){
    (function($){
        // Booking form
        $(document).on('spu.box_open',function(e,id){
            // Get title
            var title = $('h1').text();
            // Get booking date
            var booking_date = $('#tourBookingForm select[name=date]').val();
            var adult, child; // Booking form inputs
            var quantity_adult = quantity_child = false; // Booking form values
            var age;
            // Get quantities
            adult = $('#tourBookingForm input[name="quantity_adult"]');
            child = $('#tourBookingForm input[name="quantity_child"]');
            if(adult.length>0 && child.length>0) {
                quantity_adult = adult.val();
                quantity_child = child.val();
                age = ['adult', 'child'];
            } else {
                adult = $('#tourBookingForm input[name="quantity"]');
                if(adult.length>0) {
                    quantity_adult = adult.val();
                    age = ['adult'];
                }
            }
            // Pop-up form
            var form = $('.spu-content .form-enquiry');
            // Set title
            form.find('input[name=title]').val(title).prop('readonly', true);
            // Set booking date
            form.find('input[name=booking-date]').val(booking_date).prop('readonly', true);
            // Set quantities
            adult = form.find('input[name=adult]');
            if(quantity_adult) {
                adult.val(quantity_adult).show();
            } else {
                form.find('span.adult').hide();
                adult.hide();
            }
            child = form.find('input[name=child]');
            if(quantity_child) {
                child.val(quantity_child).show();
            } else {
                form.find('span.child').hide();
                child.hide();
            }
            // Get & Set prices
            var i;
            $('#tourBookingForm span.woocommerce-Price-amount.amount').each(function (index, el){
                i = 0;
                if(index == 0) i = 1;
                if(index == 2) i = 2;
                if(age && i && age[i - 1])
                    form.find('span.' + age[i - 1] + ' .price').text(' - ' + $(el).text());
            });
        });
    })(jQuery);
}
window.addEventListener('load',initPopUpFormValues);
/*
 * Checkout form count of travelers can changed.
 * So there needed some fields for each person.
 * Fields hided and should be shown.
 */
function billingPersonsCountChanged(){
    (function($){
        $('#billing_persons_count').on('change',function(){
			var that = $(this);
			// hide all
			$('.moskito-person').hide();
			// show needed
			for(var i=0; i < that.val(); i++)
				$('#moskito-person-' + i).show();
        });
    })(jQuery);
}
window.addEventListener('load',billingPersonsCountChanged);
function showText(el,eid){
	$=jQuery;
	el.innerHTML=$('#'+eid).hasClass('hidden') ? 'Verbergen' : 'Weiterlesen';
	$('#'+eid).toggleClass('hidden');
}

document.onreadystatechange = function () {
    if (document.readyState === 'complete') {
        var headerLinks = document.querySelectorAll('.continents__links > li');
        for (var i = 0; i < headerLinks.length; i++) {
            headerLinks[i].addEventListener('click', function () {
                if (this.classList.contains('active')) {
                    for (var i = 0; i < headerLinks.length; i++) {
                        headerLinks[i].classList.remove('active');
                    }
                } else {
                    for (var i = 0; i < headerLinks.length; i++) {
                        headerLinks[i].classList.remove('active');
                    }
                    this.classList.toggle('active');
                }
            });
        }
    }
}

window.addEventListener('load',function(){
	var was=document.body.querySelectorAll('.share-buttons__item.share-buttons__item--whatsapp');
	if(!was || !was.length || was.length==0) return;
	for(var i=0;i<was.length;++i){
		was[i].innerHTML='<div class="box"><a class="count" href="javascript:void(0)" ></a></div></div>';
		was[i].addEventListener('click',sharWhatsApp);
	}
	if (document.getElementsByClassName('stepmap_export_container')) $('.stepmap_export_container').parent().css('padding', '0');
});
function sharWhatsApp(){
	if(!this || !this.dataset || !this.dataset.shareinfo) return;
	var buf=document.createElement('a');
	buf.target='_blank';
	buf.href='https://api.whatsapp.com/send?l=en&phone=&text='+encodeURIComponent(this.dataset.shareinfo);
	buf.style.display='none';
	document.body.appendChild(buf);
	buf.click();
	document.body.removeChild(buf);
}
function pageLoading(stop){
	if(typeof stop==='undefined') stop=false;
	if(stop){
		$('#full_bg').removeClass('active');
		$('#full_bg .page_loading').removeClass('active');		
	}else{
		$('#full_bg').addClass('active');
		$('#full_bg .page_loading').addClass('active');
	}
}
function printTour(el,tid){
	var map=document.querySelector('.stepmap_export_container img.stepmap_image');
	if(map) map=map.src; else map='';
	pageLoading();
	$.ajax({
		url: window.location,
		method: "POST",
		data: { "print_tour": tid, 'map' : map},
		success : function(data){
			try{
				data=JSON.parse(data);
			}catch(ex){ alert('An error occured, please try again later'); }
			if(data['type']=='error' || !data['url']){
				alert('An error occured, please try again later');
			}else{
				var buf=document.createElement('a');
				buf.style.display='none';
				buf.setAttribute('href',data['url']);
				buf.setAttribute('target','_blank');
				document.body.appendChild(buf);
				buf.click();
				document.body.removeChild(buf);
			}
			pageLoading(true);
		},
		error : function(data){
			alert('An error occured, please try again later');
			pageLoading(true);
		},
	});
}

function sentTourByEmail(el,tid,title){
	var elem=document.body.querySelector('#email_sharing');
	if(!elem) return false;
	if(title){
		elem.querySelector('.title').innerHTML=title;
	}
	elem.querySelector('form')['share_email'].value=tid;
	$(elem).addClass('active');
}
function closePopup(el){
	$(el).parents('.popup').removeClass('active');
}
function sendEmailSharing(el,ev){
	if(!el || !el.form) return false;
	ev.preventDefault();
	var loader=el.form.querySelector('.ajax_loading');
	var result=el.form.querySelector('.ajax_result');
	el.disabled=true;
	$(loader).addClass('active');
	$(result).removeClass('active');
	$.ajax({
		url: window.location,
		method: "POST",
		data: $(el.form).serialize(),
		success : function(data){
			res_text='An error occured, please try again later';
			try{
				data=JSON.parse(data);
				if(data['type']=='success'){
					res_text=data['text'];					
				}else{
					res_text='An error occured, please try again later';					
				}
			}catch(ex){}
			
			$(loader).removeClass('active');
			$(result).addClass('active');
			result.innerHTML=res_text;
			el.disabled=false;
		},
		error : function(data){
			$(loader).removeClass('active');
			$(result).addClass('active');
			el.disabled=false;
			result.innerHTML='An error occured, please try again later';
		},
	});
}

window.addEventListener('load',function(){
	(function($){
		if ($('div').is('.lstour_header')) {
			$('.lstour_header .lstour__details p').on('click',function () {
				var sortID = $(this).attr('id'),
					sortData = '';
				if ($(this).hasClass('ascending')) {
					$('.lstour_header .lstour__details p').removeClass('ascending descending');
					$(this).removeClass('ascending').addClass('descending');
					sortData = 'desc';
				} else if ($(this).hasClass('descending')) {
					$('.lstour_header .lstour__details p').removeClass('ascending descending');
					$(this).removeClass('descending').addClass('ascending');
					sortData = 'asc';
				} else {
					$('.lstour_header .lstour__details p').removeClass('ascending descending');
					$(this).addClass('ascending');
					sortData = 'asc';
				}
				sortTours(sortID, sortData);
			});
		}
	})(jQuery);
});
function sortTours(sortType, sortData) {
	var tours = [];
	$('.lstour:not(.lstour_header)').each(function (i,el) {
		var duration = $(el).find('.lstour__duration').html(),
			dateFrom = $(el).find('.lstour__from').html(), // dd.mm.yyyy
			price = $(el).find('.lstour__price').html();
		duration = duration.match(/\d+/);
		dateFrom = dateFrom.match(/(\d{2}).(\d{2}).(\d{4})/);  // [0][dd][mm][yyyy]
		dateFrom = new Date(dateFrom[3], dateFrom[2] -1, dateFrom[1]); // need yyyy, mm, dd
		price = price.replace(/\D+/g,'');

		tours[i] = {
			duration: +duration[0],
			dateFrom: dateFrom,
			price: price,
			tour: el
		}
	});
	switch (sortType) {
		case 'sort_duration':
			tours.sort(function(a, b){
				if (sortData == 'desc') return a.duration-b.duration;
				if (sortData == 'asc') return b.duration-a.duration;
			});
			break;
		case 'sort_date_from':
			tours.sort(function(a, b){
				if (sortData == 'desc') return a.dateFrom-b.dateFrom;
				if (sortData == 'asc') return b.dateFrom-a.dateFrom;
			});
			break;
		case 'sort_price':
			tours.sort(function(a, b){
				if (sortData == 'desc') return a.price-b.price;
				if (sortData == 'asc') return b.price-a.price;
			});
			break;
		default:
			console.log('sort didnt work');
	}
	for (i=0; i < tours.length; ++i) {
		$('.lstour_header').after(tours[i].tour);
	}
}
window.addEventListener('load',function(){
	var after=document.querySelector('#billing_persons_count_field');
	if(!after) return false;
	var els=document.querySelectorAll('.moskito-person');
	for(var i=0;i<els.length;++i){
		if(after.nextElementSibling){
			el=after.nextElementSibling;
		}else el=after;
		el.parentNode.insertBefore(els[i],el);
		after=els[i];
	}
});