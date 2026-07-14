<?php

declare(strict_types=1);

namespace TheFoxLab\TflSocial\Providers\Meta;

final class GraphFields
{
    /**
     * @return list<string>
     */
    public static function facebookPage(): array
    {
        return [
            'id',
            'name',
            'category',
            'picture{url}',
            'about', // Page description
            'fan_count', // Page likes
            'followers_count', // Followers
            'verification_status', // blue_verified/gray_verified
            'website',
            'phone',
            'location{city,state,country,street,zip}', // Business address
            'cover{source}', // Cover photo URL
            'username', // @username
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function facebookPageDiscovery(): array
    {
        return [
            'id',
            'name',
            'access_token',
            'category',
            'tasks', // CRITICAL: MANAGE/CREATE_CONTENT needed for most actions
            'picture{url}',
            'verification_status',
            'instagram_business_account{id,username}', // Check if IG linked
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function facebookBusinesses(): array
    {
        return [
            'id',
            'name',
            'primary_page{id,name}', // Main Page of Business
            'verification_status',
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function facebookFeed(): array
    {
        return [
            'id',
            'message',
            'story',
            'created_time',
            'updated_time', // When edited
            'permalink_url',
            'full_picture', // Deprecated but still works
            'attachments{media_type,media{image{src}},title,description,unshimmed_url,target{id}}', // Better than full_picture
            'status_type', // added_photos, shared_story, etc
            'from{id,name}', // Who posted it
            'shares{count}',
            'reactions.summary(true)', // Total reactions
            'comments.summary(true)', // Comment count
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function facebookPosts(): array
    {
        return self::facebookFeed();
    }
    
    /**
     * @return list<string>
     */
    /**
     * @return list<string>
     */
    public static function facebookPhotos(): array
    {
        return [
            'id',
            'name', // Caption
            'picture', // Thumbnail ~130px - for grid
            'source', // Main/original - for popup
            'width', // For aspect ratio calc
            'height',
            'created_time',
            'link', // FB link
            'album{id,name}',
            // Remove these if you don't need them:
            // 'images', // All 8 sizes
            // 'webp_images', // All 8 WebP sizes
            // 'updated_time',
            // 'from{id,name}',
            // 'comments.summary(true)', // Extra API call
        ];
    }
    
    /**
 * @return list<string>
 */
public static function facebookVideos(): array
{
    return [
        'id',
        'title',
        'description',
        'length', // seconds
        'created_time',
        'updated_time',
        'permalink_url',
        'picture', // Thumbnail ~400px - fast for grid
        'thumbnails{uri,is_preferred}', // Add this: only returns preferred/HD thumb
        'source', // Main MP4 for popup player - only works for owner
        'embed_html', // Fallback if source is null
        'status', // Check if 'ready' before showing
        'views', // Needs pages_read_engagement
        'live_status', // LIVE/VOD/LIVE_STOPPED
    ];
}
    
        public static function facebookAlbums(): array
        {
            return [
                'id',
                'name',
                'description',
                'count',
                'cover_photo{id,source,picture}', // Removed images - was pulling 6 sizes
                'created_time',
                'link',
                'type',
                // Remove: updated_time, from{id,name}, privacy - rarely needed for dashboards
            ];
        }
    
    /**
     * @return list<string>
     */
    public static function facebookEvents(): array
    {
        return [
            'id',
            'name',
            'description',
            'start_time',
            'end_time',
            'event_times', // For recurring events
            'place{id,name,location{city,country,street,zip,latitude,longitude}}',
            'cover{source}',
            'ticket_uri', // Ticket link
            'attending_count',
            'maybe_count',
            'interested_count',
            'declined_count',
            'is_canceled',
            'is_draft',
            'type', // public/private/community
            'category',
            'owner{id,name}',
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function facebookReviews(): array
    {
        return [
            'reviewer{id,name}', // Removed picture{url} - slows it down
            'rating',
            'review_text',
            'created_time',
            'recommendation_type', // positive/negative
            // Remove: open_graph_story{id}, has_rating, has_review - useless
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramBusinessAccount(): array
    {
        return [
            'id',
            'username',
            'name',
            'profile_picture_url',
            'ig_id', // Legacy IG ID
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramProfile(): array
    {
        return [
            'id',
            'username',
            'name',
            'biography',
            'website',
            'profile_picture_url',
            'followers_count', // Needs instagram_manage_insights
            'follows_count', // Needs instagram_manage_insights
            'media_count',
            'ig_id',
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramMedia(): array
    {
        return [
            'id',
            'caption',
            'media_type',
            'media_product_type',
            'media_url', // Main - IMAGE/VIDEO full size
            'thumbnail_url', // Thumb - only for VIDEO/REELS, else null
            'permalink',
            'timestamp',
            'username',
            'like_count',
            'comments_count',
            // Remove these if you don't need:
            // 'is_comment_enabled',
            // 'is_shared_to_feed',
            // 'owner{id,username}',
            // 'children{...}', // Move to instagramCarousel() only
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramMediaById(): array
    {
        return [
           ...self::instagramMedia(),
            'insights.metric(impressions,reach,saved,shares)', // Needs instagram_manage_insights
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramReels(): array
    {
        return [
           ...self::instagramMedia(),
            'video_title', // Reels can have title
            'audio_name', // Music used
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramCarousel(): array
    {
        return [
            ...self::instagramMedia(),
            'children{id,media_type,media_url,thumbnail_url,permalink}', // Added thumbnail_url
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramStories(): array
    {
        return [
            'id',
            'media_type',
            'media_product_type',
            'media_url',
            'thumbnail_url',
            'timestamp',
            'permalink',
            'expiry', // When story expires
            'is_shared_to_feed',
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramInsights(): array
    {
        return [
            'impressions',
            'reach',
            'saved',
            'shares',
            'likes',
            'comments',
            'engagement',
            'profile_views', // Profile level only
            'website_clicks', // Profile level only
            'follower_count', // Profile level only
            'video_views', // Media level only
            'plays', // Reels only
            'total_interactions',
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramHashtagSearch(): array
    {
        return [
            'id',
            'name',
        ];
    }
    
    /**
     * @return list<string>
     */
    public static function instagramHashtagMedia(): array
    {
        return [
            'id',
            'caption',
            'media_type',
            'media_url',
            'permalink',
            'timestamp',
            'children{id,media_url,media_type}',
            // Remove: like_count, comments_count - always 0 for hashtag search
        ];
    }
}
