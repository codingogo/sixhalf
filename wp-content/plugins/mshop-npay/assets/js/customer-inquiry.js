jQuery(function(a){function b(b,d,e){c.block({message:null}),a.ajax({type:"post",dataType:"json",url:_mnp.ajax_url,data:{action:_mnp.slug+"-answer_customer_inquiry",param:{InquiryID:b,AnswerContent:d,AnswerContentID:e}},success:function(b){c.unblock(),b.success?(alert("답변이 등록되었습니다."),a("div.mshop-customer-inquiry-list form").submit()):alert("오류가 발생했습니다. 잠시 후, 다시 시도해주세요.")},error:function(a){alert("오류가 발생했습니다. 잠시 후, 다시 시도해주세요."),c.unblock()}})}a.blockUI.defaults.css.border="none",a.blockUI.defaults.css.width="32px",a.blockUI.defaults.css.height="32px",a.blockUI.defaults.css.background="transparent",a.blockUI.defaults.overlayCSS.opacity=.3;var c=a("table.customer_inquiry");a("div.mshop-customer-inquiry-list").find(".datepicker").datepicker({dateFormat:"yy-mm-dd"}).datepicker("setDate",_mnp.search_date),a("div.mshop-customer-inquiry-list").find("a.title").click(function(){return a(this).parent().find("div.InquiryContent").toggleClass("hide"),!1}),a("div.mshop-customer-inquiry-list").find("input.button-answer").click(function(){var c,d=a(this).data("inquiry-id"),e=a(this).data("answer-content-id"),f=a(this).parent().find("textarea.answer").val();return c=e?"답변을 수정하시겠습니까?":"답변을 등록하시겠습니까?",confirm(c)&&b(d,f,e),!1})});