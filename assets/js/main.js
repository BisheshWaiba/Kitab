function scrollGenres(direction) {
    const container = document.getElementById('genreScroll');
    const scrollAmount = 300;
    if (direction === 'left') {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

function switchCategory(catId, btn) {
    // Update chips
    document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Update content
    document.querySelectorAll('.category-content').forEach(c => c.classList.remove('active'));
    const content = document.getElementById(catId);
    if (content) {
        content.classList.add('active');
    }
}
