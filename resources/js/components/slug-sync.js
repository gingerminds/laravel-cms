document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-slug-source]').forEach((target) => {
        const source = document.querySelector(target.dataset.slugSource);

        if (!source) return;

        const overwrite = target.dataset.slugOverwrite === 'true';
        let userEdited = false;
        let timer;

        target.addEventListener('input', () => {
            userEdited = true;
        });

        source.addEventListener('input', () => {
            clearTimeout(timer);

            timer = setTimeout(() => {
                if (userEdited) return;
                if (!overwrite && target.value) return;

                target.value = slugify(source.value);
            }, 1000);
        });
    });
});

function slugify(value) {
    return value
        .normalize('NFKD')
        .replace(/\p{M}/gu, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}
