/* Knowledge Exchange Hub - Live Mentor Search JS */
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');
    const proficiencySelect = document.getElementById('proficiencySelect');
    const minRatingSelect = document.getElementById('minRatingSelect');
    const mentorsContainer = document.getElementById('mentorsContainer');

    if (!mentorsContainer) return;

    let debounceTimer;

    function triggerSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchMentors, 300);
    }

    function fetchMentors() {
        const query = searchInput ? searchInput.value.trim() : '';
        const cat = categorySelect ? categorySelect.value : '';
        const prof = proficiencySelect ? proficiencySelect.value : '';
        const rating = minRatingSelect ? minRatingSelect.value : '0';

        const url = `/KEH/api/search-mentors.php?query=${encodeURIComponent(query)}&category_id=${cat}&proficiency=${encodeURIComponent(prof)}&min_rating=${rating}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderMentors(data.mentors);
                }
            })
            .catch(err => console.error("Search error:", err));
    }

    function renderMentors(mentors) {
        mentorsContainer.innerHTML = '';

        if (mentors.length === 0) {
            mentorsContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="p-4 bg-white rounded-4 shadow-sm d-inline-block" style="max-width: 450px;">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No Mentors Found</h5>
                        <p class="text-muted small">Try adjusting your search keyword or clearing category filters to discover more students.</p>
                    </div>
                </div>
            `;
            return;
        }

        mentors.forEach(m => {
            let skillsHtml = '';
            m.teach_skills.forEach(s => {
                let badgeClass = 'badge-prof-intermediate';
                if (s.proficiency_level === 'Beginner') badgeClass = 'badge-prof-beginner';
                if (s.proficiency_level === 'Advanced') badgeClass = 'badge-prof-advanced';
                if (s.proficiency_level === 'Expert') badgeClass = 'badge-prof-expert';

                skillsHtml += `<span class="skill-chip ${badgeClass} mb-1 me-1">${escapeHtml(s.skill_name)} (${s.proficiency_level})</span>`;
            });

            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4 mb-4';
            card.innerHTML = `
                <div class="keh-card h-100 p-4 d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        <img src="/KEH/uploads/profile_photos/${m.photo}" 
                             onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(m.name)}&background=6366f1&color=fff';"
                             alt="${m.name}" class="mentor-avatar me-3">
                        <div>
                            <h5 class="fw-bold mb-1">${escapeHtml(m.name)}</h5>
                            <p class="text-muted small mb-1"><i class="fas fa-university me-1 text-primary"></i> ${escapeHtml(m.college)}</p>
                            <span class="badge bg-purple-subtle rounded-pill">${escapeHtml(m.department)}</span>
                        </div>
                    </div>

                    <p class="text-secondary small mb-3 flex-grow-1">${escapeHtml(m.bio ? m.bio.substring(0, 100) + '...' : 'Available to teach and guide fellow students.')}</p>

                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold mb-1">Teaches:</label>
                        <div>${skillsHtml || '<span class="text-muted small">No active skills listed</span>'}</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                        <div class="small">
                            ${m.rating_stars} <span class="fw-bold ms-1">${m.rating > 0 ? m.rating + '/5' : 'New'}</span>
                            <span class="text-muted ms-1">(${m.total_sessions} sessions)</span>
                        </div>
                        <div>
                            <a href="/KEH/mentor-details.php?id=${m.id}" class="btn btn-outline-primary btn-sm rounded-pill px-3">View Profile</a>
                        </div>
                    </div>
                </div>
            `;
            mentorsContainer.appendChild(card);
        });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.innerText = str;
        return div.innerHTML;
    }

    if (searchInput) searchInput.addEventListener('input', triggerSearch);
    if (categorySelect) categorySelect.addEventListener('change', triggerSearch);
    if (proficiencySelect) proficiencySelect.addEventListener('change', triggerSearch);
    if (minRatingSelect) minRatingSelect.addEventListener('change', triggerSearch);
});
