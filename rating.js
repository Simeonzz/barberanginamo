// rating.js - Rating system functionality

// Show staff ratings modal
function showStaffRatings(staffId, staffName) {
    document.getElementById('ratingsStaffName').textContent = staffName + ' - Reviews';
    document.getElementById('staffRatingsModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    fetch('get_staff_ratings.php?staff_id=' + staffId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update average
                document.getElementById('averageRating').textContent = data.average.toFixed(1);
                document.getElementById('totalRatings').textContent = data.total + ' ratings';
                
                // Update stars
                const starsContainer = document.getElementById('ratingStars');
                starsContainer.innerHTML = '';
                const average = data.average;
                
                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement('span');
                    star.style.fontSize = '1.2rem';
                    if (i <= Math.floor(average)) {
                        star.innerHTML = '★';
                        star.style.color = '#d4af37';
                    } else if (i === Math.floor(average) + 1 && average % 1 >= 0.5) {
                        star.innerHTML = '½';
                        star.style.color = '#d4af37';
                    } else {
                        star.innerHTML = '☆';
                        star.style.color = '#ddd';
                    }
                    starsContainer.appendChild(star);
                }
                
                // Update comments
                const commentsDiv = document.getElementById('ratingsComments');
                commentsDiv.innerHTML = '';
                
                if (data.comments.length === 0) {
                    commentsDiv.innerHTML = '<div class="no-reviews">No reviews yet</div>';
                } else {
                    data.comments.forEach(comment => {
                        const commentDiv = document.createElement('div');
                        commentDiv.className = 'comment-item';
                        
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            stars += i <= comment.rating ? '★' : '☆';
                        }
                        
                        commentDiv.innerHTML = `
                            <div class="comment-user">${comment.user_name}</div>
                            <div class="comment-stars">${stars}</div>
                            <div class="comment-text">${comment.comment || '<em>No comment</em>'}</div>
                            <div class="comment-date">${new Date(comment.created_at).toLocaleDateString()}</div>
                        `;
                        commentsDiv.appendChild(commentDiv);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading ratings:', error);
        });
}

// Close staff ratings modal
function closeStaffRatings() {
    document.getElementById('staffRatingsModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

// Load ratings for all staff
function loadAllStaffRatings() {
    // This function will be populated with PHP loop
}

// Initialize rating badges
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking on overlay
    const overlay = document.getElementById('modalOverlay');
    if (overlay) {
        overlay.addEventListener('click', closeStaffRatings);
    }
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeStaffRatings();
        }
    });
});