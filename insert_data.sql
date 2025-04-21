INSERT INTO movies (title, original_title, slug, description, release_year, duration, quality, thumbnail, type, episodes_count, status, featured)
VALUES 
('One Piece', 'One Piece', 'one-piece', 'One Piece là câu chuyện về Monkey D. Luffy, một cậu bé có ước mơ trở thành Vua Hải Tặc và tìm được kho báu vĩ đại nhất thế giới mang tên "One Piece".', 1999, '24 phút/tập', '1080p', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'anime', 1000, 1, 1),
('Naruto Shippuden', 'Naruto Shippuden', 'naruto-shippuden', 'Naruto Shippuden là một bộ anime nổi tiếng, kể về hành trình của Naruto Uzumaki sau 2 năm rèn luyện.', 2007, '23 phút/tập', '720p', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'anime', 500, 1, 1),
('Attack on Titan', 'Shingeki no Kyojin', 'attack-on-titan', 'Attack on Titan là một bộ anime nổi tiếng về cuộc chiến sinh tồn của loài người trước các Titan.', 2013, '24 phút/tập', '1080p', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'anime', 75, 1, 1),
('Spider-Man: No Way Home', 'Spider-Man: No Way Home', 'spider-man-no-way-home', 'Spider-Man phải đối mặt với kẻ thù từ nhiều vũ trụ khác nhau sau khi Doctor Strange thực hiện một câu thần chú nguy hiểm.', 2021, '148 phút', '4K', 'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1', 'movie', 1, 1, 1),
('The Batman', 'The Batman', 'the-batman', 'Batman khám phá tham nhũng ở Gotham City trong khi đối đầu với kẻ giết người hàng loạt Riddler.', 2022, '176 phút', '4K', 'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1', 'movie', 1, 1, 1),
('Top Gun: Maverick', 'Top Gun: Maverick', 'top-gun-maverick', 'Sau hơn 30 năm phục vụ, Pete "Maverick" Mitchell trở lại để huấn luyện cho phi đội TOPGUN đặc biệt.', 2022, '130 phút', 'FullHD', 'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1', 'movie', 1, 1, 1);

INSERT INTO episodes (movie_id, episode_number, title, description, duration, thumbnail)
VALUES 
(1, 1, 'Ta là Luffy! Người sẽ trở thành Vua Hải Tặc!', 'Tập đầu tiên của series One Piece.', 24, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f'),
(1, 2, 'Xuất hiện! Thợ săn hải tặc Zoro!', 'Luffy gặp Zoro - kiếm sĩ tài ba nhưng đang bị bắt giữ.', 24, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f'),
(2, 1, 'Trở về', 'Naruto trở về làng Lá sau 2 năm tu luyện.', 23, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f'),
(3, 1, 'Với bạn sau 2000 năm', 'Tập đầu tiên của Attack on Titan.', 24, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f'),
(4, 1, 'Spider-Man: No Way Home', 'Bộ phim đầy đủ.', 148, 'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1'),
(5, 1, 'The Batman', 'Bộ phim đầy đủ.', 176, 'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1'),
(6, 1, 'Top Gun: Maverick', 'Bộ phim đầy đủ.', 130, 'https://images.unsplash.com/photo-1615986200762-a1ed9610d3b1');
