---
name: ui-debug
description: >
  Phương pháp debug lỗi giao diện (UI bugs) theo tư duy "State Delta" — tìm
  chính xác điểm behavior thay đổi thay vì đoán nguyên nhân. Dùng skill này
  khi gặp bất kỳ lỗi UI/CSS/JS nào: element bị vỡ layout, sai kích thước,
  không hiển thị đúng, khác nhau giữa editor và frontend, khác nhau giữa
  các môi trường, hoặc lỗi chỉ xảy ra trong một số điều kiện nhất định.
  Đặc biệt hiệu quả với các bug khó tái hiện, bug chỉ xảy ra sau khi
  toggle/scroll/resize, hoặc bug liên quan đến theme/plugin phức tạp như
  Elementor, The7, WPBakery, v.v.
---

# UI Debug — State Delta Framework

## Nguyên tắc cốt lõi

> **Bug giao diện = Sự khác biệt giữa 2 states**
>
> Câu hỏi không phải *"tại sao nó sai"* mà là:
> **"Cái gì khác nhau giữa state ĐÚNG và state SAI?"**

Đây gọi là **State Delta** — tìm delta trước, tìm nguyên nhân sau.

---

## Bước 1: Đọc triệu chứng đúng cách

Trước khi làm bất cứ điều gì, khai thác tối đa thông tin từ mô tả lỗi.

Mỗi câu mô tả lỗi đều chứa **ít nhất 1 clue ẩn**:

| Người dùng nói | Clue thực sự |
|---|---|
| "Editor đúng, frontend sai" | Theme/plugin JS can thiệp khi render |
| "Trước đúng, giờ sai" | Có thay đổi gì đó — update, cache, config |
| "Bật rồi tắt mới hết" | JS chưa được initialized ở default state |
| "Chỉ sai trên mobile" | Breakpoint CSS hoặc JS responsive conflict |
| "Sau khi scroll mới sai" | Sticky/fixed positioning hoặc scroll event JS |
| "Chỉ sai khi login" | CSS/JS load khác nhau cho user roles |
| "Đôi khi sai, đôi khi đúng" | Race condition hoặc cache không nhất quán |

**→ Luôn liệt kê clues trước khi đề xuất fix.**

---

## Bước 2: Xác định 2 States

Điền vào bảng này trước khi debug:

```
State A (ĐÚNG):   [môi trường / điều kiện / thời điểm]
State B (SAI):    [môi trường / điều kiện / thời điểm]
Delta (khác biệt): [những gì khác nhau giữa A và B]
```

Ví dụ từ case The7 sticky:
```
State A (ĐÚNG):   Elementor Editor / Sau khi toggle sticky on→off
State B (SAI):    Frontend / Page load lần đầu, sticky chưa được toggle
Delta:            The7 sticky JS chưa được initialized
```

---

## Bước 3: 5 Trục Delta

Sau khi có 2 states, phân tích theo 5 trục để tìm delta:

### Trục 1 — Environment Delta
```
Editor ≠ Frontend
Development ≠ Production  
Cache enabled ≠ Cache disabled
Logged in ≠ Logged out
Plugin active ≠ Plugin inactive
```
**Test:** Mở Incognito + tắt cache → vẫn lỗi?

### Trục 2 — Time Delta
```
Trước khi JS load ≠ Sau khi JS load
Trước scroll ≠ Sau scroll
Trước toggle ≠ Sau toggle
Trước resize ≠ Sau resize
```
**Test:** Disable JavaScript → vẫn lỗi? → CSS/HTML problem, không phải JS.

### Trục 3 — Layer Delta
```
HTML → CSS → JavaScript → Plugin → Theme → Server
```
**Quy trình loại trừ từng layer:**
1. Disable JS → vẫn lỗi? → Không phải JS
2. Remove inline styles → vẫn lỗi? → Không phải inline CSS
3. Disable plugin một cái → hết lỗi? → Plugin đó là culprit
4. Switch sang default theme → hết lỗi? → Theme problem

### Trục 4 — Scope Delta
```
Tất cả pages ≠ Chỉ 1 page
Tất cả sections ≠ Chỉ section X
Tất cả elements ≠ Chỉ element Y
Desktop ≠ Tablet ≠ Mobile
```
**Test:** Reproduce lỗi trên trang blank/mới → vẫn lỗi? → Global problem.

### Trục 5 — Data/State Delta
```
Default value ≠ Saved value ≠ Runtime value
Database state ≠ JS runtime state ≠ CSS computed state
```
**Test:** Inspect element → Computed tab → giá trị thực tế là gì so với expected?

---

## Bước 4: Quy trình 3 bước ISOLATE → DIFF → PROVE

### ISOLATE — Thu hẹp đến smallest reproducible case
- Tắt hết plugins không liên quan
- Test trên trang blank
- Xóa hết custom code không liên quan
- Mục tiêu: **Tìm điều kiện tối thiểu để reproduce bug**

### DIFF — So sánh working vs broken
```javascript
// Dùng DevTools để so sánh
// Bước 1: Chụp computed styles ở state ĐÚNG
// Bước 2: Chụp computed styles ở state SAI  
// Bước 3: Tìm dòng khác nhau
```
- Elements tab → Computed → Filter bằng property nghi ngờ
- Network tab → So sánh requests giữa 2 states
- Console tab → Tìm errors/warnings

### PROVE — Verify bằng cách reproduce có chủ đích
- Fix xong → **cố tình reproduce lại bug** theo đúng điều kiện cũ
- Nếu không reproduce được → fix đúng
- Nếu reproduce được → fix chưa đủ hoặc sai nguyên nhân

---

## Bước 5: DevTools Commands Nhanh

```javascript
// Xem tất cả computed styles của element
window.getComputedStyle(document.querySelector('.your-selector'))

// Xem kích thước thực tế
document.querySelector('.your-selector').getBoundingClientRect()

// Tìm CSS rule nào đang override
// → Elements tab → Computed → click vào property → thấy nguồn file

// Xem element có bị hidden không
document.querySelector('.your-selector').offsetParent // null = hidden

// Force trigger JS events để test
window.dispatchEvent(new Event('scroll'))
window.dispatchEvent(new Event('resize'))
```

## References Theo Case

- WordPress/Gutenberg footer đã render nhưng layout vẫn sai, `alignfull` tràn ngang, wrapper `site-footer` lệch âm, hoặc có khoảng trắng dưới footer: đọc `references/footer-full-width-overflow.md`.

---

## Anti-patterns Cần Tránh

### ❌ Shotgun approach
```
"Thử clear cache, thử tắt plugin, thử đổi CSS, thử regenerate..."
→ Không có logic loại trừ, mất thời gian, may rủi
```

### ❌ Fix trước khi tìm delta
```
"Thêm !important vào CSS" trước khi biết nguyên nhân
→ Che giấu bug thay vì fix bug
```

### ❌ Assume thay vì verify
```
"Chắc là cache" → flush cache → vẫn lỗi → mất thời gian
→ Luôn verify assumption bằng test cụ thể
```

### ✅ Đúng: Đọc clue → Tìm delta → Loại trừ từng layer → Prove fix

---

## Template Báo Cáo Bug

Khi debug cho người khác, luôn yêu cầu đủ thông tin này:

```
1. Môi trường SAI:   [browser, device, login status, cache status]
2. Môi trường ĐÚNG:  [browser, device, login status, cache status]  
3. Bước tái hiện:    [step by step để reproduce]
4. Expected:         [trông như thế nào khi đúng]
5. Actual:           [trông như thế nào khi sai]
6. Đã thử:           [những gì đã thử và kết quả]
7. Screenshots:      [cả 2 states nếu có thể]
```

---

## Checklist Debug Nhanh (30 giây)

Trước khi đào sâu, chạy qua checklist này:

- [ ] Hard refresh (Ctrl+Shift+R) → hết chưa?
- [ ] Incognito mode → hết chưa?
- [ ] Disable tất cả cache → hết chưa?
- [ ] Disable JS → lỗi thay đổi không?
- [ ] Default theme → hết chưa?
- [ ] Lỗi ở tất cả pages hay chỉ 1 page?
- [ ] Lỗi ở tất cả elements hay chỉ 1 element?
- [ ] Lỗi xuất hiện ngay hay sau action nào đó?

Mỗi câu trả lời **loại trừ** ít nhất 1 layer, thu hẹp phạm vi debug.
