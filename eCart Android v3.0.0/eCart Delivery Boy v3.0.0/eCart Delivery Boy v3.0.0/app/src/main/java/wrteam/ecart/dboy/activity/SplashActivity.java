package wrteam.ecart.dboy.activity;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.view.Window;
import android.view.WindowManager;

import androidx.appcompat.app.AppCompatActivity;

import wrteam.ecart.dboy.R;
import wrteam.ecart.dboy.helper.Constant;
import wrteam.ecart.dboy.helper.Session;

public class SplashActivity extends AppCompatActivity {


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        requestWindowFeature(Window.FEATURE_NO_TITLE);
        getWindow().setFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN,
                WindowManager.LayoutParams.FLAG_FULLSCREEN);

        setContentView(R.layout.activity_splash);

        final Session session = new Session(getApplicationContext());

        int SPLASH_TIME_OUT = 2000;

        new Handler().postDelayed(new Runnable() {

            @Override
            public void run() {
                if (session.isUserLoggedIn()) {
                    session.setData(Constant.OFFSET, "" + 0);
                    startActivity(new Intent(SplashActivity.this, MainActivity.class).addFlags(Intent.FLAG_ACTIVITY_CLEAR_TASK).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK));
                } else {
                    startActivity(new Intent(SplashActivity.this, LoginActivity.class).addFlags(Intent.FLAG_ACTIVITY_CLEAR_TASK).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK));
                }
            }
        }, SPLASH_TIME_OUT);
    }
}