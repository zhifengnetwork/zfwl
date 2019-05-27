$(function(){
//当键盘键被松开时发送Ajax获取数据
		$('.form-control').keyup(function(){
			var keywords = $(this).val();
			if (keywords==' ') { $('#word').hide(); return };
			$.ajax({
				url: '/goods/search_goods?name=' + keywords,
				dataType: 'json',
				success:function(data){
					
					console.log(data.data)
					$('#word').empty().show();					              
                    $.each(data.data, function(){
						$('#word').append('<div class="click_work">'+ this.goods_name +'</div>');
						$('#word').append('<input type="hidden" name="goods_id" value="'+this.goods_id+'" class="form-control_goods_id"/>');
					})
                    
				},
				error:function(){
					$('#word').empty().show();
					$('#word').append('<div class="click_work">Fail "' + keywords + '"</div>');
				}
			})
           


		})
//点击搜索数据复制给搜索框
		$(document).on('click','.click_work',function(){
			var word = $(this).text();
			$('.form-control').val(word);
			$('#word').hide();
			// $('#texe').trigger('click');触发搜索事件
		})

	})