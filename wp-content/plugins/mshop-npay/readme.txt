=== 우커머스 네이버페이 – MShop Woocommerce NAVER PAY ===
Contributors: Codemstory
Donate link: http://www.codemshop.com/
Tags: 네이버, 네이버페이, 네이버결제, 네이버 간편결제, 우커머스 네이버페이, 이니시스, KCP, 앤페이, 대한민국 결제, 결제, 코드엠샵, Naver, Naverpay, NPay Korea Payment, incis, Pay
Requires at least: 4.6.0
Tested up to: 4.8.4
Stable tag: 1.2.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

엠샵 네이버페이 플러그인은 워드프레스 우커머스 쇼핑몰에서 네이버 아이디로 결제를 진행할 수 있는 플러그인 입니다.

== Description ==

[Korean]

대한민국에 네이버 아이디 없는 분 없죠?
엠샵 네이버페이 플러그인은 워드프레스 우커머스 쇼핑몰에서 네이버 아이디로 결제를 진행할 수 있는 플러그인 입니다.

네이버 아이디로 다양한 가맹점에서 회원가입 없이 편리하게 결제를 진행할 수 있는 네이버페이는, 카드 혜택과 별개로 네이버페이 포인트 적립 및 결제가 가능하여, 폭팔적인 사용자 증가를 불러오고 있는 네이버의 결제 서비스 입니다.
따라서, 쇼핑몰에 유입된 비회원 고객을 구매회원으로 전환할 수 있어, 빠른 시일 내 판매 매출 상승을 기대할 수 있습니다.

<상세 기능>

- 네이버 아이디로 쉽고, 빠르게 결제하세요.
- 네이버페이는 간단하고 편리한 안전 결제를 지원합니다.
- 대한민국의 쇼핑 구매자들이 가장 자주, 가장 많이 이용하는 결제는 네이버페이 입니다.
- 네이버페이로 비회원의 매출 향상을 기대하셔도 좋아요.

<상세 매뉴얼>

※  엠샵 매뉴얼 사이트에서 이용 스크린 샷 을 확인할 수 있습니다.
http://manual.codemshop.com/docs/naverpay2/

== Installation ==

[Korean]

1. 워드프레스 플러그인 화면에서 MShop Naverpay 검색하여 직접 설치하거나, 다운로드 받은 파일을 /wp-content/plugins/mshop-naverpay 디렉토리에 업로드하여 설치 합니다.
2. MShop Naverpay 플러그인을 활성화합니다.
3. MShop Naverpay 메뉴에서 플러그인 설정을 진행 합니다.

== Frequently Asked Questions ==

= 엠샵 네이버페이 플러그인 설치만으로 네이버페이 이용이 가능한가요? =

네이버페이 플러그인 설치 후 네이버페이 센터(https://admin.pay.naver.com) 에 방문하여 입점 및 검수 완료 후 쇼핑몰에서 네이버페이를 이용할 수 있습니다.

※  네이버페이 대한 사용 설명 자주하는 질문은 FAQ 가이드를 참고 해 주세요. http://manual.codemshop.com/docs/naverpay2/faq

== Screenshots ==

1. 상품 상세 페이지
2. 장바구니 페이지
3. 네이버페이 관리자 화면
4. 네이버페이 간편결제 진행과정
5. 네이버페이 주문 상세 페이지
6. 네이버페이 주문 상세 화면

== Upgrade Notice ==

Not yet.

== Changelog ==

= 1.2.6 =
WC 3.2.6 Support.
Change the default order status of Bacs Order
WC 3.2.6 지원
무통장 입금건의 기본 주문 상태 변경

= 1.2.4 =
Shipping Order Status Label modified.
When SKU is used, Wishlist Product information lookup error modified.
WC 2.6.x - User ID setting for Naver Pay order When ordering logged in user
When using filter at the same time process modified. (mnp_product_id, mnp_merchant_product_id)
Check the payment method specified when invoicing.
배송중 주문상태 레이블 변경
SKU 사용시 찜하기 상품정보 조회 오류 수정
WC 2.6.x 로그인한 사용자 주문시 네이버페이 주문에 사용자 아이디 설정
mnp_product_id, mnp_merchant_product_id 필터 동시 사용시 오류
송장 등록시 지정된 결제 수단 체크

= 1.2.1 =
Added personal clearance unique code for overseas delivery.
Invoice bulk upload and automatic shipping registration function added.
Added automatic shipping registration function after outputting invoice When using Goodsflow service.
MShop DIY Product Plugin support.
NPay Additional items purchase function added.
해외직배송 개인통관 고유부호 추가.
송장 일괄 업로드 및 자동 배송 등록 기능 추가.
굿스플로 서비스 이용시 송장 출력 후 자동 배송 등록 기능 추가.
엠샵 DIY 상품 플러그인 지원.
NPay 추가상품 구매 기능 추가.

= 1.1.6 =
Added filter for Custom Data registration when registering NPay Order.
Added filter for Custom Data Checking when Creating and Refreshing NPay Order.
Save Custom Data as Order Meta data when Creating and Refreshing NPay Order.
네이버페이 주문 등록시 커스텀 데이터 등록을 위한 필터 추가.
네이버페이 주문 생성 및 새로고침 시 커스텀 데이터 확인을 위한 필터 추가.
네이버페이 주문 생성 및 새로고침 시 커스텀 데이터를 주문 메타정보로 저장.

= 1.1.5 =
Added filter NPay Purchase Processing in Cart.
장바구니에서 네이버페이 구매 처리 필터 추가.

= 1.1.4 =
Added filter for NPay Review Writer, Status and Content edit.
네이버페이 리뷰 작성자, 상태 및 컨텐츠 편집을 위한 필터 추가.

= 1.1.3 =
Sold Out Product process modified at NPay Order reload.
네이버페이 주문 새로고침시 품절상품에 대한 처리 수정.

= 1.1.2 =
NPay Order Complete time process modified.
NPay 주문건에 대한 결제 완료 시간 기록 처리 수정.

= 1.1.1 =
Dashboard - According to ViewDuration option Change labels added.
대쉬보드 - 조회기간 옵션에 따른 레이블 변경 기능 추가.

= 1.1.0 =
NPay 대시보드 추가.
옵션상품에 네이버페이 버튼 항상 표시 여부 지정 가능.
로그인한 회원의 경우, 회원 주문으로 생성.
주문관리 화면에서 네이버페이 주문번호, 상품주문번호 검색 기능.
NPay Dashboard added.
Variable Product NPay button always display option added.
If Logged in User, User Order make.
At Order Management page, NPay Order ID, Product Order ID Find function added.

= 1.0.16 =
NPay 주문건은 우커머스 입금대기 자동취소시간이 적용되지 않도록 처리.
NPay Order Woocommerce Deposit Waiting Auto Cancer time process modified.

= 1.0.15 =
NPay Order Refresh Sold Out Product excluded modified.
네이버페이 주문 새로고침시 품절상품이 제외되는 오류 수정.
When Cancer/Return After the Second Order Item of Order, Order Information update process modified.
주문에 포함된 2번째 이후 주문 아이템 취소/반품시 주문정보창의 금액이 갱신되지 않는 오류 수정.

= 1.0.14 =
External Image URL Filter Added.
외부 이미지 URL 필터 추가

= 1.0.13 =
WPML supported.
WPML 지원.

= 1.0.12 =
Shipping company information updated.
택배사 정보 업데이트.

= 1.0.11 =
Image URL protocol setting added.
이미지 URL 프로토콜 설정 추가

= 1.0.10 =
plugin notice and alarm added.
플러그인 공지사항 및 알람 기능 추가

= 1.0.9 =
WC 2.6.X & WC 3.0.X Improved compatibility.
WC 2.6.x, 3.0.x 호환성 개선
WC 3.0 - Order Information Refresh Order total calc modified.
WC 3.0 - 주문정보 새로고침시 주문총계 계산 수정

= 1.0.8 =
WC 2.6.X Order Item Meta Display modified.
WC 2.6.X 주문 아이템 표시 수정

= 1.0.7 =
WC 3.0 Payment method not displayed modified.
WC 3.0 결제 수단 미노출 수정

= 1.0.6 =
NPay screen switch mode setting added.
WC 2.6.X Variable Product variable process modified.
WC 3.0.X Order Meta process modified.
네이버페이 화면전환방식 설정 기능 추가
WC 2.6.X 옵션상품 옵션처리 수정
WC 3.0.X 주문 메타정보 처리 수정

= 1.0.5 =
all cart item remove process script modified.
모든 장바구니 상품 삭제시 처리 스크립트 수정.

= 1.0.4 =
NPay Order information update process modified.
NPay 주문 정보 갱신 오류 수정.

= 1.0.3 =
Variable Product image URL & product information verify process modified.
옵션상품 이미지URL 조회 및 상품정보조회 오류 수정.

= 1.0.2 =
First Release.
최초 릴리즈.