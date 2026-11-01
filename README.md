# Sergio AI Studio

> **Sergio AI Studio** là plugin WordPress tối ưu hóa SEO và kiến tạo nội dung tự động chuyên nghiệp, tích hợp Google Gemini AI kết hợp phương pháp luận tiên tiến **Claude-SEO** và liên kết chặt chẽ với **Rank Math SEO (Free & PRO)**.

---

## 🌟 Tính Năng Nổi Bật

### 1. Claude-SEO & AEO/GEO Engine (Generative Engine Optimization)
- **Answer-First Hook:** Cung cấp câu trả lời trực diện 2-3 câu chứa Focus Keyword ở đoạn mở đầu, tối ưu hóa trích xuất trực tiếp (Passage Citability) cho Google AI Overviews, Perplexity và ChatGPT Search.
- **Key Takeaways Box:** Bảng tóm tắt nhanh các điểm quan trọng ngay đầu bài.
- **Semantic Hierarchy (H2, H3):** Cấu trúc tiêu đề phân cấp logic, thẻ H2 chứa Focus Keyword và các thực thể ngữ nghĩa LSI.
- **Mục FAQ Chuẩn SEO:** Bắt buộc có phần Câu Hỏi Thường Gặp (3-4 câu hỏi H3) ở cuối bài viết để chiếm vị trí People Also Ask (PAA) và đón đầu tìm kiếm AI.
- **Contextual Image ALT:** Tự động bổ sung thẻ ALT giàu ngữ cảnh và chứa từ khóa cho mọi hình ảnh.

### 2. Audit & Tối Ưu SEO On-Page Toàn Diện (Posts & Pages)
- Quét sức khỏe SEO tổng thể toàn site cho cả **Bài Viết (Posts)** và **Trang Tĩnh (Pages)**.
- Thang điểm 100 theo chuẩn Rank Math với bảng kiểm định chi tiết 14 tiêu chí.
- **AI Nâng Cấp Nội Dung:** Tự động mở rộng bài viết đạt từ 1,000 - 1,800 từ chuyên sâu (Helpful Content & E-E-A-T), đẩy điểm On-Page đạt từ **85 đến 98+ điểm (Xanh Rank Math)**.
- **Kế Hoạch Cải Thiện Toàn Site 1-Click:** Tự động phát hiện và xử lý hàng loạt các trang thiếu từ khóa, thiếu mô tả hoặc điểm kém.

### 3. Nghiên Cứu Chủ Đề & Tự Động Viết Bài Theo Lịch (WP-Cron)
- **Topic Analyzer:** Phân tích ý định tìm kiếm, bóc tách góc nhìn khai thác độc đáo và từ khóa ngách.
- **Hàng Đợi Nội Dung (Queue):** Quản lý danh sách chủ đề theo chu kỳ tự động.
- **Lịch Trình WP-Cron Linh Hoạt:** Cấu hình tần suất viết bài tự động (theo giờ tùy chọn hoặc các mốc chuẩn), tự động trích xuất chủ đề và xuất bản.

### 4. Thu Thập & Tái Bút Nội Dung (Crawl & Rewrite)
- Web Crawler thu thập thông tin nguồn uy tín, phân tích dữ kiện thực tế (Fact-Pack).
- Tái cấu trúc và viết lại bài viết độc bản, chống đạo văn, giữ trọn vẹn thông điệp giá trị.

### 5. Bảo Mật Cấp Doanh Nghiệp (Enterprise Security)
- Mã hóa API Key hai chiều bằng thuật toán **AES-256-CBC** kết hợp với WordPress Salts (`AUTH_SALT`, `SECURE_AUTH_KEY`).
- Hỗ trợ khai báo API Key cố định qua file `wp-config.php` (`SERGIO_AI_GEMINI_API_KEY`).
- Mặt nạ hiển thị bảo mật cho API Key trên giao diện quản trị (`••••••••••••••••xxxx`).
- Phân quyền nghiêm ngặt (`manage_options`), kiểm tra Nonce chống tấn công CSRF trên 100% Endpoint AJAX.
- Lọc dữ liệu đầu ra và mã HTML bằng hệ thống lọc an toàn bảo vệ cấu trúc bài viết.

---

## 🚀 Cài Đặt & Kích Hoạt

1. Đặt thư mục plugin vào đường dẫn:
   ```
   wp-content/plugins/sergio-ai-studio
   ```
2. Kích hoạt plugin trong trang **Plugins (Gói mở rộng)** của WordPress Admin.
3. Truy cập menu **Sergio AI Studio** > **Cài Đặt** để nhập **Google Gemini API Key**.
4. (Tùy chọn) Khai báo API Key trong `wp-config.php`:
   ```php
   define( 'SERGIO_AI_GEMINI_API_KEY', 'AIzaSy...' );
   ```

---

## 🛠️ Cấu Trúc Mã Nguồn

```
sergio-ai-studio/
├── assets/
│   ├── css/
│   │   └── admin-studio.css        # Giao diện Modern Light Studio
│   └── js/
│       └── admin-studio.js         # AJAX điều phối, tương tác thời gian thực
├── includes/
│   ├── class-admin.php             # Router, menu và render dashboard
│   ├── class-article-writer.php    # Động cơ viết bài Gemini
│   ├── class-content-rewriter.php  # Tái bút và mở rộng nội dung
│   ├── class-db.php                # Quản lý cơ sở dữ liệu custom tables
│   ├── class-fact-pack-engine.php  # Xử lý thông tin và dữ kiện
│   ├── class-gemini-client.php     # Client kết nối Gemini 2.5 Flash API
│   ├── class-logger.php            # Ghi nhật ký thực thi (Audit Logs)
│   ├── class-rankmath-bridge.php   # Tương thích Rank Math Free & PRO
│   ├── class-scheduler.php         # Quản lý hàng đợi và WP-Cron
│   ├── class-security.php          # Mã hóa AES-256-CBC & bảo mật HTML
│   ├── class-seo-audit.php         # Bộ máy Claude-SEO On-Page & Audit
│   ├── class-topic-analyzer.php    # Phân tích ý định tìm kiếm
│   └── class-web-crawler.php       # Crawler thu thập dữ liệu nguồn
├── .gitignore
├── README.md
├── sergio-ai-studio.php            # File bootstrap plugin
└── uninstall.php                   # Quy trình dọn dẹp khi gỡ bỏ
```

---

## 📄 Bản Quyền
Phát triển bởi **Sergio Themes & AI Team**.
Giấy phép: GPL-2.0-or-later.
