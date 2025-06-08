document.addEventListener("DOMContentLoaded", () => {
  document.body.addEventListener("click", (e) => {
    const btn = e.target.closest(".slb-like-btn");
    if (!btn) {
      console.warn("Could not find .slb-like-btn");
      return;
    }
    e.preventDefault();
    const postId = btn.closest("[data-post-id]")?.dataset?.postId;
    btn.setAttribute("aria-disabled", "true");

    fetch(slb_data.ajax_url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "slb_like_post",
        nonce: slb_data.nonce,
        post_id: postId,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.data && typeof data.data.count !== "undefined") {
          btn.classList.add("liked");
          btn.querySelector(".slb-like-count").textContent = data.data.count;

          // 🔧 Update icon and label immediately
          btn.querySelector(".like-icon").textContent = "💖";
          btn.querySelector(".like-label").textContent = "Liked";
        } else {
          console.warn(data.data?.message || "Like failed");
        }
      })
      .catch((err) => {
        console.error("AJAX error", err);
      });
  });
});
