// Mirrors gingerminds-media-manager's category-tree.js so the "choose a
// category" modal looks and behaves identically for pages and media —
// same markup/CSS classes (.category-tree, .category-tree-item,
// .toggle-icon, .category-tree-children), different element IDs (to avoid
// collisions if both modals ever end up on the same page) and query
// param (`category_id` instead of `media_category_id`).
document.addEventListener('DOMContentLoaded', () => {
    const tree = document.getElementById('pageCategoryTree');

    if (!tree) return;

    const confirmBtn = document.getElementById('btnConfirmPageCategory');
    const selectedIdInput = document.getElementById('selectedPageCategoryId');
    const selectedLabel = document.getElementById('selectedPageCategoryLabel');

    tree.addEventListener('click', function (e) {
        const toggleIcon = e.target.closest('.toggle-icon');
        const item = e.target.closest('.category-tree-item');

        if (!item) return;

        if (toggleIcon) {
            const children = item.nextElementSibling;
            if (children?.classList.contains('category-tree-children')) {
                children.classList.toggle('open');
                toggleIcon.querySelector('i').classList.toggle('bi-chevron-right');
                toggleIcon.querySelector('i').classList.toggle('bi-chevron-down');
            }
            return;
        }

        tree.querySelectorAll('.category-tree-item').forEach(el => el.classList.remove('selected'));
        item.classList.add('selected');

        selectedIdInput.value = item.dataset.categoryId;
        selectedLabel.textContent = item.dataset.categoryName;
        confirmBtn.disabled = false;
    });

    confirmBtn.addEventListener('click', function () {
        const categoryId = selectedIdInput.value;
        const baseUrl = confirmBtn.dataset.createUrl;
        const url = categoryId ? `${baseUrl}?category_id=${categoryId}` : baseUrl;
        window.location.href = url;
    });
});
