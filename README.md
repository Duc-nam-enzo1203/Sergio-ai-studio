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
- Đánh giá theo thang điểm On-Page 100 điểm với bảng kiểm định chi tiết 14 tiêu chí dựa trên Claude-SEO.
- **AI Tối Ưu Nội Dung & Metadata:** Tái cấu trúc nội dung, bổ sung Answer-First Hook, FAQ ngữ nghĩa và đồng bộ các trường Focus Keyword, Title, Meta Description chuẩn Rank Math.
- **Kế Hoạch Cải Thiện Toàn Site:** Phát hiện và hỗ trợ xử lý các trang thiếu từ khóa, thiếu mô tả hoặc cấu trúc chưa tối ưu.

### 3. Nghiên Cứu Dữ Kiện Thật & Tự Động Viết Bài Theo Lịch (WP-Cron)
- **Fact Pack Engine:** Khai thác dữ kiện thật qua Google Search Grounding. Bắt buộc kiểm tra nguồn dẫn chứng thực tế trước khi viết bài; từ chối tạo bài nếu không đủ dữ liệu kiểm chứng.
- **Hàng Đợi Nội Dung (Queue):** Quản lý chủ đề theo chu kỳ tự động, tự động phục hồi tác vụ kẹt và giới hạn tối đa số lần thử.
- **An Toàn Xuất Bản:** Bài viết tự động luôn được đưa vào trạng thái bản nháp (`draft`) hoặc chờ duyệt (`pending`) để biên tập viên kiểm duyệt thực tế trước khi xuất bản.

### 4. Thu Thập & Tái Cấu Trúc Nội Dung (Crawl & Rewrite)
- Web Crawler tuân thủ `robots.txt`, giới hạn kích thước tải phản hồi và lưu vết URL đã xử lý để tránh thu thập trùng lặp.
- Tái cấu trúc nội dung nguồn và lưu thành bản nháp kèm trích dẫn nguồn tham chiếu minh bạch.

### 5. Bảo Mật & Xác Thực
- Mã hóa API Key hai chiều bằng phương thức **Authenticated Encrypt-then-MAC (AES-256-CBC + HMAC-SHA256)** với khóa bí mật từ WordPress Salts.
- Hỗ trợ khai báo API Key cố định qua file `wp-config.php` (`SERGIO_AI_GEMINI_API_KEY`).
- Mặt nạ hiển thị bảo mật cho API Key trên giao diện quản trị (`••••••••••••••••xxxx`).
- Phân quyền chi tiết (`edit_post` cho từng bài viết, `manage_options` cho các tác vụ hàng loạt) kèm cơ chế thời gian chờ (cooldown) chống lạm dụng tài nguyên.

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

