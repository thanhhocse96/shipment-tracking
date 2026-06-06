## State Delta Framework

Ý tưởng rất đơn giản:

> **Mọi bug giao diện đều tồn tại giữa 2 states — một state đúng, một state sai. Bug nằm ở chỗ hai states đó khác nhau, không phải ở "nguyên nhân" mà bạn đang đoán.**

---

### Vấn đề với cách debug thông thường

Khi thấy bug, não người tự động làm:

```
Thấy triệu chứng → Đoán nguyên nhân → Thử fix
```

Cái này hoạt động tốt với bug đơn giản. Nhưng với bug phức tạp (nhiều layer, nhiều plugin, nhiều môi trường), bạn có thể đoán 10 nguyên nhân mà không trúng cái nào — vì bạn đang nhìn vào **kết quả**, không nhìn vào **điểm thay đổi**.

---

### State Delta làm ngược lại

Thay vì hỏi *"tại sao nó sai"*, hỏi:

```
Khi nào nó ĐÚNG?
Khi nào nó SAI?
Hai thời điểm đó khác nhau ở chỗ nào?
```

Cái "khác nhau ở chỗ nào" đó chính là **delta** — và delta đó **chứa bug**.

---

### Ví dụ từ case The7

Nếu debug theo kiểu thông thường:
```
Icon bị to → Có thể là CSS? → Thử !important
           → Có thể là cache? → Flush cache  
           → Có thể là plugin? → Tắt từng plugin
           → ... 6 LLM không ra
```

Nếu debug theo State Delta:
```
State ĐÚNG:  Elementor editor / sau khi toggle sticky
State SAI:   Frontend / page load lần đầu

Delta:       → Editor không chạy The7 JS
             → Frontend chạy The7 JS  
             → Sticky chưa toggle = JS chưa init
             
Bug nằm ở:  JS initialization, không phải CSS
```

Từ câu mô tả *"bật rồi tắt mới hết"* — đó chính là clue cho thấy **JS cần được triggered ít nhất 1 lần**. Thông tin đã đủ để tìm ra nguyên nhân mà không cần đoán.

---

### Tại sao nó hiệu quả

Vì nó **loại trừ** thay vì **liệt kê**. Mỗi lần xác định được một delta, bạn loại bỏ toàn bộ một nhóm nguyên nhân không thể là culprit. Đến cuối quá trình, không gian nguyên nhân còn lại rất nhỏ — và thường chỉ có một cái.

## Ý tưởng - Sử dụng cách tư duy của đạo hàm

### Đạo hàm và State Delta

Trong toán, đạo hàm là:

```
f'(x) = lim  f(x + Δx) - f(x)
        Δx→0 ─────────────────
                    Δx
```

Ý nghĩa: **tại điểm x, nếu thay đổi input một chút thì output thay đổi bao nhiêu?**

State Delta trong debug cũng hỏi y chang:

```
Δbehavior = f(state_sai) - f(state_đúng)
```

**Tại điểm nào đó, input thay đổi một chút — behavior thay đổi hoàn toàn.**

---

### Điểm tương đồng sâu hơn

Đạo hàm tìm **điểm thay đổi** của hàm số. State Delta tìm **điểm thay đổi** của hệ thống. Cả hai đều không quan tâm đến toàn bộ hàm — chỉ quan tâm đến **vùng lân cận của sự thay đổi**.

Trong calculus, khi hàm số nhảy đột ngột tại một điểm — đó là nơi thú vị nhất để nghiên cứu. Bug cũng vậy — nó luôn nằm tại **điểm nhảy** giữa đúng và sai.

---

### Một điểm khác biệt

Đạo hàm cho phép Δx tiến về 0 — càng nhỏ càng chính xác.

Trong debug, bạn cũng làm y vậy khi **isolate**: thu hẹp điều kiện tái hiện bug đến mức nhỏ nhất có thể. Càng isolate được sát, delta càng rõ, nguyên nhân càng hiển nhiên.

> Isolate chính là quá trình cho Δx tiến về 0 trong debug.