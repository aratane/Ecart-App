package wrteam.ecart.dboy.helper;

import android.app.Activity;
import android.app.Application;
import android.content.Context;
import android.content.SharedPreferences;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.toolbox.ImageLoader;
import com.android.volley.toolbox.Volley;

import wrteam.ecart.dboy.R;

public class AppController extends Application {

    public static final String TAG = AppController.class.getSimpleName();
    private static AppController mInstance;
    private RequestQueue mRequestQueue;
    private SharedPreferences sharedPref;
    private com.android.volley.toolbox.ImageLoader mImageLoader;

    public static Boolean isConnected(final Activity activity) {
        boolean check = false;
        ConnectivityManager ConnectionManager = (ConnectivityManager) activity.getSystemService(Context.CONNECTIVITY_SERVICE);
        NetworkInfo networkInfo = ConnectionManager.getActiveNetworkInfo();


        if (networkInfo != null && networkInfo.isConnected()) {
            check = true;
        }  /*  Toast.makeText(activity, "Check Internet Connection..!!", Toast.LENGTH_SHORT).show();
            new AlertDialog.Builder(activity)
                    .setView(v)
                    .setPositiveButton("Refresh", new DialogInterface.OnClickListener() {
                        public void onClick(DialogInterface dialog, int which) {
                            dialog.dismiss();
                            isConnected(activity);
                        }
                    })
                    .show();*/
        return check;
    }

    public static synchronized AppController getInstance() {
        return mInstance;
    }

    public static String toTitleCase(String str) {
        if (str == null) {
            return null;
        }
        boolean space = true;
        StringBuilder builder = new StringBuilder(str);
        final int len = builder.length();

        for (int i = 0; i < len; ++i) {
            char c = builder.charAt(i);
            if (space) {
                if (!Character.isWhitespace(c)) {
                    // Convert to title case and switch out of whitespace mode.
                    builder.setCharAt(i, Character.toTitleCase(c));
                    space = false;
                }
            } else if (Character.isWhitespace(c)) {
                space = true;
            } else {
                builder.setCharAt(i, Character.toLowerCase(c));
            }
        }

        return builder.toString();
    }

    public com.android.volley.toolbox.ImageLoader getImageLoader() {
        getRequestQueue();
        if (mImageLoader == null) {
            mImageLoader = new ImageLoader(this.mRequestQueue, new BitmapCache());
        }
        return this.mImageLoader;
    }

    @Override
    public void onCreate() {
        super.onCreate();
        mInstance = this;
        sharedPref = this.getSharedPreferences(getString(R.string.app_name), Context.MODE_PRIVATE);


    }

    public String getData(String id) {
        return sharedPref.getString(id, "");
    }

    public String getDeviceToken() {
        return sharedPref.getString("DEVICETOKEN", "");
    }

    public void setDeviceToken(String token) {
        sharedPref.edit().putString("DEVICETOKEN", token).apply();
    }

    public RequestQueue getRequestQueue() {
        if (mRequestQueue == null) {
            mRequestQueue = Volley.newRequestQueue(getApplicationContext());
        }

        return mRequestQueue;
    }

    public <T> void addToRequestQueue(Request<T> req) {
        req.setTag(TAG);
        getRequestQueue().add(req);
    }
}
