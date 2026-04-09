# Google Maps API Setup Guide

This guide explains how to create a Google Maps API key for RepTrack.

## Why Do You Need It?

- **Shortened Plus Codes** (e.g., "QP4V+9X Sfax") require Google Maps API
- **Standard Plus Codes** (e.g., "8FRC+GQ4+XX") work WITHOUT API key
- API is used only as a fallback for shortened codes

## Step-by-Step Instructions

### 1. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click on the project dropdown (top left)
3. Click **"New Project"**
4. Enter a project name (e.g., "RepTrack")
5. Click **"Create"**

### 2. Enable Maps JavaScript API

1. Make sure your new project is selected
2. Go to [APIs & Services > Library](https://console.cloud.google.com/apis/library)
3. Search for "Maps JavaScript API"
4. Click on **"Maps JavaScript API"**
5. Click **"Enable"**

### 3. Enable Geocoding API

1. Go back to [APIs & Services > Library](https://console.cloud.google.com/apis/library)
2. Search for "Geocoding API"
3. Click on **"Geocoding API"**
4. Click **"Enable"**

### 4. Create API Key

1. Go to [APIs & Services > Credentials](https://console.cloud.google.com/apis/credentials)
2. Click **"Create Credentials"** > **"API Key"**
3. Your API key will be created automatically
4. **Copy the API key** (you'll need it in step 5)

### 5. Configure API Key Security (Recommended)

1. Click the **Edit** icon (pencil) next to your API key
2. Under **"Application restrictions"**, choose:
   - **"IP addresses"** for production servers
   - Or **"None"** for testing (not recommended for production)
3. Under **"API restrictions"**, choose:
   - **"Restrict key"** 
   - Select:
     - **Maps JavaScript API**
     - **Geocoding API**
4. Click **"Save"**

### 6. Add API Key to RepTrack

1. Open the `.env` file in your RepTrack root directory
2. Add or update this line:
   ```env
   GOOGLE_MAPS_API_KEY=your_api_key_here
   ```
3. Replace `your_api_key_here` with your actual API key
4. Save the file
5. Clear your browser cache or refresh the page

## Pricing

Google Maps API offers a **$200 monthly free credit**:

| Service | Free Tier | After Free Tier |
|---------|-----------|----------------|
| Geocoding API | $200/month credit | $5.00 per 1,000 requests |
| Maps JavaScript API | $200/month credit | $7.00 per 1,000 requests |

**Estimated Costs:**
- 1,000 Plus Code lookups: ~$5.00
- 10,000 Plus Code lookups: ~$50.00
- Most small to medium businesses stay within free tier

## Cost Saving Tips

1. **Use Standard Plus Codes** when possible (e.g., "8FRC+GQ4+XX")
   - These work 100% client-side without API
   - No cost, instant decoding

2. **Use Shortened Codes** only when necessary
   - Require API lookup
   - Use for sharing with non-technical users

3. **Monitor Usage**
   - Check [Google Cloud Console](https://console.cloud.google.com/apis/dashboard)
   - Set up budget alerts to avoid surprise charges

## Testing Your API Key

After adding the API key:

1. Open a contact profile page in RepTrack
2. Enter a shortened Plus Code like: `QP4V+9X Sfax`
3. Click "Enregistrer"
4. If successful, coordinates will be updated
5. If you see an error, check:
   - API key is correct in `.env`
   - APIs are enabled (Steps 2 & 3)
   - Your account has billing enabled

## Troubleshooting

### Error: "API key invalid"
- Check the API key in `.env` matches exactly
- Ensure no extra spaces or quotes

### Error: "API key restricted"
- Check your API key restrictions in Google Cloud Console
- Make sure your server IP is allowed (if using IP restriction)

### Error: "Quota exceeded"
- You've used your $200 monthly credit
- Enable billing in Google Cloud Console
- Or wait until next month for free tier to reset

### Error: "Zero results"
- The Plus Code format might be incorrect
- Try a different format (e.g., "8FRC+GQ4+XX" vs "QP4V+9X Sfax")

## Security Best Practices

1. **Never commit `.env` file** to git
2. **Use environment-specific keys** (dev, staging, production)
3. **Restrict API key by IP** in production
4. **Monitor usage regularly** for unusual activity
5. **Rotate API keys** if compromised

## Additional Resources

- [Google Maps API Documentation](https://developers.google.com/maps/documentation)
- [Google Cloud Billing](https://cloud.google.com/billing/docs/how-to)
- [Open Location Code (Plus Code) Reference](https://github.com/google/open-location-code)

## Support

If you encounter issues:

1. Check Google Cloud Console error logs
2. Review API usage dashboard
3. Verify API key restrictions
4. Ensure billing is enabled (even for free tier)

---

**Note:** RepTrack's client-side Plus Code decoding works for standard codes without any API key. The Google Maps API is only needed for shortened codes or additional geocoding features.