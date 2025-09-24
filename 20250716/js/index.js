document.addEventListener("DOMContentLoaded", function() {

  // 初始化每个商品的按钮状态
  document.querySelectorAll(".productbox").forEach(function(productBox) {
    const defaultVariant = productBox.querySelector(".color-box.active");
    const cartButton = productBox.querySelector(".cart-form button");
    if (defaultVariant && cartButton) {
      const stock = parseInt(defaultVariant.dataset.stock);
      if (stock <= 0) {
        cartButton.disabled = true;
        cartButton.style.background = "#ccc";
        cartButton.style.cursor = "not-allowed";
      } else {
        cartButton.disabled = false;
        cartButton.style.background = "";
        cartButton.style.cursor = "pointer";
      }
    }
  });

  // 点击切换颜色逻辑
  document.querySelectorAll(".color-box").forEach(function(box) {
    box.addEventListener("click", function() {
      // 如果库存为0，直接返回
      if (this.classList.contains("disabled")) return;

      const newImage = this.dataset.image;
      const stock = parseInt(this.dataset.stock);
      const variantId = this.dataset.variantId;

      const productBox = this.closest(".productbox");
      const img = productBox.querySelector(".product-image");
      const stockInfo = productBox.querySelector(".stock-info span");
      const variantInput = productBox.querySelector(".variant-id-field");
      const cartButton = productBox.querySelector(".cart-form button");

      // 切换图片
      if (img) img.src = newImage;

      // 更新库存
      if (stockInfo) stockInfo.textContent = stock;

      // 更新隐藏字段
      if (variantInput) variantInput.value = variantId;

      // 更新选中状态
      productBox.querySelectorAll(".color-box").forEach(b => b.classList.remove("active"));
      this.classList.add("active");

      // 更新按钮状态
      if (cartButton) {
        if (stock <= 0) {
          cartButton.disabled = true;
          cartButton.style.background = "#ccc";
          cartButton.style.cursor = "not-allowed";
        } else {
          cartButton.disabled = false;
          cartButton.style.background = "";
          cartButton.style.cursor = "pointer";
        }
      }
    });
  });

});
