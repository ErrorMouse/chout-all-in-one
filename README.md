# Chout - All in One: Cẩm nang Phát triển dành cho AI

Tệp này đóng vai trò là hướng dẫn chính thức dành cho bất kỳ trợ lý AI nào khi thêm tính năng mới hoặc sửa đổi mã nguồn của plugin `Chout - All in One`.

## 1. Đăng ký & Sắp xếp Tính năng
- **Thứ tự Bảng chữ cái:** Bất cứ khi nào thêm một tính năng mới, nó PHẢI được chèn vào mảng `features()` trong tệp `chout-all-in-one.php` theo đúng **thứ tự bảng chữ cái** dựa trên khóa (key) của mảng.
- **Tính Mô-đun:** Mỗi tính năng phải có thư mục riêng và tệp `.php` riêng, được đặt tên theo định dạng `kebab-case`.
- **Đồng bộ Giao diện (UI/UX):** 
  - Tuyệt đối KHÔNG sử dụng thẻ `<table class="form-table">` để hiển thị các trường cấu hình (Settings fields). Bảng chỉ nên dùng khi cần hiển thị danh sách nhiều dữ liệu. Thay vào đó, hãy bọc các trường cài đặt trong `<div class="caio-card">`.
  - Tuyệt đối KHÔNG sử dụng `<div class="wrap">` mặc định của WordPress. Giao diện trang cài đặt phải được bọc trong cấu trúc HTML tiêu chuẩn của plugin như sau:
  ```html

  <div class="chout-background-effect"></div>

  <div id="chout-aio-(slug-của-tính-năng)" class="chout-all-in-one">
      <div class="caio-wrap">
          <h1>Chout - (Tên tính năng)</h1>

          <div id="chout-donate">
						<span class="author">
							By 
							<a href="https://profiles.wordpress.org/nmtnguyen56/" target="_blank" rel="noopener noreferrer">
								Chout
							</a>
						</span>
						<span class="donate">
							<?php chout_caio_donate_link_html(); ?>
						</span>
					</div>
          
          <?php if ( $saved ) : ?>
              <div id="caio-toast-notification" class="caio-toast show">
                  <?php esc_html_e( 'Changes saved.', 'chout-all-in-one' ); ?>
              </div>
              <script>
                  setTimeout(function(){
                      var toast = document.getElementById("caio-toast-notification");
                      if(toast) { toast.className = toast.className.replace("show", ""); }
                  }, 3000);
              </script>
          <?php endif; ?>

          <form method="post" action="">
              <?php wp_nonce_field( '...', '...' ); ?>
              <div class="caio-card">
                  ...
              </div>
              <p style="margin-top: 20px;"><?php submit_button( __( 'Save Changes', 'chout-all-in-one' ), 'primary', 'submit', false ); ?></p>
          </form>
      </div>
  </div>
  ```

## 2. Tiêu chuẩn Bảo mật & Plugin Check
Tất cả mã nguồn phải tuân thủ nghiêm ngặt Tiêu chuẩn Mã nguồn WordPress (WPCS) và vượt qua bài kiểm tra của plugin `Plugin Check` chính thức mà không có lỗi hoặc cảnh báo nào.
- **Truy cập Trực tiếp:** Mỗi tệp PHP phải bắt đầu bằng:
  ```php
  if ( ! defined( 'ABSPATH' ) ) { exit; }
  ```
- **Đóng gói Lớp (Class):** Bọc mỗi lớp (class) trong câu lệnh kiểm tra `if ( ! class_exists( '...' ) )`. Các lớp phải có tiền tố `Chout_AIO_`.
- **Khử trùng & Xác thực:** Tất cả dữ liệu đầu vào của người dùng (ví dụ: `$_POST`, `$_GET`, `$_FILES`) phải được xác thực bằng `isset()` và làm sạch bằng các hàm như `sanitize_text_field( wp_unslash( ... ) )` trước khi sử dụng.
- **Thoát dữ liệu (Escaping):** Tất cả dữ liệu động xuất ra màn hình phải được thoát bằng `esc_html()`, `esc_attr()`, `esc_url()`, hoặc `wp_kses_post()`.
- **Nonces & Quyền hạn:** Tất cả các biểu mẫu gửi đi và các yêu cầu làm thay đổi trạng thái phải xác minh nonce và kiểm tra quyền của người dùng (`current_user_can()`).
- **Thao tác Tệp tin:** Sử dụng các phương thức của `WP_Filesystem` thay vì các hàm tệp tin PHP trực tiếp (`fopen`, `file_put_contents`, v.v.) khi tương tác với các tệp hệ thống.

## 3. Ngôn ngữ & Dịch thuật
- **Ưu tiên Tiếng Anh:** Tất cả mã nguồn, chú thích, biến và các chuỗi văn bản hiển thị cho người dùng trong bộ mã PHẢI được viết bằng Tiếng Anh.
- **Hàm Dịch thuật:** Tất cả các chuỗi phải được bọc trong các hàm dịch thuật của WordPress (ví dụ: `__()`, `esc_html__()`) với text domain là `'chout-all-in-one'`.
- **Cập nhật Tệp POT & PO:** 
  - Sau khi thêm một tính năng, hãy thêm các chuỗi mới vào cuối tệp `languages/chout-all-in-one.pot`.
  - Thêm các bản dịch vào tệp `languages/chout-all-in-one-vi.po`.
  - **ĐẶC BIỆT LƯU Ý:** **Tên Tính năng** (Feature Name) PHẢI GIỮ NGUYÊN TIẾNG ANH bên trong tệp `.po` (không được dịch tên tính năng sang tiếng Việt). Các mô tả, nhãn và thông báo khác thì dịch bình thường.

## 4. Tối ưu hóa & Thực hành Tốt nhất
- **Tải theo Điều kiện:** Chỉ nạp (enqueue) các tệp CSS và JavaScript trên các trang cụ thể mà chúng thực sự cần thiết (ví dụ: kiểm tra `$hook_suffix` trong admin).
- **Tránh Biến Toàn cục:** Không làm bẩn không gian tên toàn cục (global namespace). Giữ logic bên trong các phương thức tĩnh của lớp (static methods).
- **Hiệu suất Cơ sở Dữ liệu:** Dọn dẹp sạch sẽ mọi `options` hoặc `transients` do tính năng tạo ra bên trong tệp `uninstall.php`.
