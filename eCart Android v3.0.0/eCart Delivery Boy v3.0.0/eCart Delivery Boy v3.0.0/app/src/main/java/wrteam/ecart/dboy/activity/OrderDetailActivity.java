package wrteam.ecart.dboy.activity;

import android.Manifest;
import android.annotation.SuppressLint;
import android.app.Activity;
import android.app.AlertDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Color;
import android.graphics.drawable.ColorDrawable;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.RelativeLayout;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.RequiresApi;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.snackbar.Snackbar;
import com.google.gson.Gson;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.Objects;

import wrteam.ecart.dboy.R;
import wrteam.ecart.dboy.adapter.ItemListAdapter;
import wrteam.ecart.dboy.helper.ApiConfig;
import wrteam.ecart.dboy.helper.AppController;
import wrteam.ecart.dboy.helper.Constant;
import wrteam.ecart.dboy.helper.Session;
import wrteam.ecart.dboy.helper.VolleyCallback;
import wrteam.ecart.dboy.model.Items;
import wrteam.ecart.dboy.model.OrderList;

import static wrteam.ecart.dboy.helper.ApiConfig.disableSwipe;

public class OrderDetailActivity extends AppCompatActivity {

    TextView tvDate, tvName, tvPhone, tvAddress, tvDeliveryTime, tvItemTotal,
            tvTaxAmt, tvPCAmount, tvWallet, tvFinalTotal, tvPaymentMethod, tvDiscountAmount, tvDeliveryCharge;

    Button btnDeliveryStatus, btnGetDirection;
    RecyclerView recyclerViewItems;
    SwipeRefreshLayout SwipeRefresh;
    ArrayList<Items> itemArrayList;
    ItemListAdapter itemListAdapter;
    Toolbar toolbar;
    Activity activity;
    RelativeLayout lyt_order_detail;
    String orderID, latitude, longitude;
    private Session session;
    int checkedItem;
    String otp;
    String[] updatedStatus;


    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_order_detail);

        toolbar = findViewById(R.id.toolbar);
        activity = OrderDetailActivity.this;
        session = new Session(activity);

        orderID = getIntent().getStringExtra(Constant.ORDER_ID);

        setSupportActionBar(toolbar);
        getSupportActionBar().setTitle(getString(R.string.product_detail) + orderID);
        getSupportActionBar().setDisplayHomeAsUpEnabled(true);

        tvDate = findViewById(R.id.tvDate);
        tvName = findViewById(R.id.tvName);
        tvPhone = findViewById(R.id.tvPhone);
        tvAddress = findViewById(R.id.tvAddress);
        tvDeliveryTime = findViewById(R.id.tvDeliveryTime);
        tvItemTotal = findViewById(R.id.tvItemTotal);
        tvDeliveryCharge = findViewById(R.id.tvDeliveryCharge);
        btnDeliveryStatus = findViewById(R.id.btnDeliveryStatus);
        tvTaxAmt = findViewById(R.id.tvTaxAmt);
        tvPCAmount = findViewById(R.id.tvPCAmount);
        tvFinalTotal = findViewById(R.id.tvFinalTotal);
        tvPaymentMethod = findViewById(R.id.tvPaymentMethod);
        tvWallet = findViewById(R.id.tvWallet);
        tvDiscountAmount = findViewById(R.id.tvDiscountAmount);

        lyt_order_detail = findViewById(R.id.lyt_order_detail);

        SwipeRefresh = findViewById(R.id.SwipeRefresh);

        btnGetDirection = findViewById(R.id.btnGetDirection);

        recyclerViewItems = findViewById(R.id.recyclerViewItems);

        recyclerViewItems.setLayoutManager(new LinearLayoutManager(activity));


        if (AppController.isConnected(activity)) {
            getOrderData(activity);
        } else {
            setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry), Color.RED);
        }

        SwipeRefresh.setColorSchemeResources(R.color.colorPrimary);

        SwipeRefresh.setOnRefreshListener(new SwipeRefreshLayout.OnRefreshListener() {
            @Override
            public void onRefresh() {
                if (AppController.isConnected(activity)) {
                    getOrderData(activity);
                    SwipeRefresh.setRefreshing(false);
                    disableSwipe(SwipeRefresh);
                } else {
                    setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry), Color.RED);
                }
                SwipeRefresh.setRefreshing(false);
            }
        });

    }

    public void getOrderData(final Activity activity) {
        if (AppController.isConnected(activity)) {

            Map<String, String> params = new HashMap<String, String>();
            params.put(Constant.ID, session.getData(Constant.ID));
            params.put(Constant.ORDER_ID, orderID);
            params.put(Constant.GET_ORDERS_BY_DELIVERY_BOY_ID, Constant.GetVal);

            ApiConfig.RequestToVolley(new VolleyCallback() {
                @SuppressLint("SetTextI18n")
                @Override
                public void onSuccess(boolean result, String response) {
                    if (result) {
                        try {
                            JSONObject jsonObject = new JSONObject(response);

                            if (!jsonObject.getBoolean(Constant.ERROR)) {
                                JSONArray jsonArray = jsonObject.getJSONArray(Constant.DATA);
                                JSONObject jsonObject1 = jsonArray.getJSONObject(0);

                                tvDate.setText(getString(R.string.order_on) + jsonObject1.getString(Constant.DATE_ADDED));
                                otp = jsonObject1.getString(Constant.OTP);
                                tvName.setText(getString(R.string._name) + jsonObject1.getString(Constant.NAME));
                                tvPhone.setText(jsonObject1.getString(Constant.MOBILE));
                                tvAddress.setText(getString(R.string.at) + jsonObject1.getString(Constant.ADDRESS));
                                btnDeliveryStatus.setText(AppController.toTitleCase(jsonObject1.getString(Constant.ACTIVE_STATUS)));
                                tvDeliveryTime.setText(getString(R.string.delivery_by) + jsonObject1.getString(Constant.DELIVERY_TIME));
                                tvDeliveryCharge.setText(new Session(activity).getData(Constant.CURRENCY) + jsonObject1.getString(Constant.DELIVERY_CHARGE));
                                if (jsonObject1.getString(Constant.LATITUDE).equals("0") && jsonObject1.getString(Constant.LONGITUDE).equals("0")) {
                                    btnGetDirection.setVisibility(View.GONE);
                                } else {
                                    latitude = jsonObject1.getString(Constant.LATITUDE);
                                    longitude = jsonObject1.getString(Constant.LONGITUDE);
                                }
                                tvItemTotal.setText(new Session(activity).getData(Constant.CURRENCY) + jsonObject1.getString(Constant.TOTAL));
                                tvPCAmount.setText(new Session(activity).getData(Constant.CURRENCY) + jsonObject1.getString(Constant.PROMO_DISCOUNT));
                                tvDiscountAmount.setText(new Session(activity).getData(Constant.CURRENCY) + jsonObject1.getString(Constant.DISCOUNT));
                                tvWallet.setText(new Session(activity).getData(Constant.CURRENCY) + jsonObject1.getString(Constant.STR_WALLET_BALANCE));
                                tvFinalTotal.setText(new Session(activity).getData(Constant.CURRENCY) + jsonObject1.getString(Constant.FINAL_TOTAL));

                                tvPaymentMethod.setText(getString(R.string.via) + jsonObject1.getString(Constant.PAYMENT_METHOD).toUpperCase());
                                tvTaxAmt.setText(jsonObject1.getString(Constant.TAX));
//
                                itemArrayList = new ArrayList<>();

                                JSONArray jsonArrayItems = new JSONArray(jsonObject1.getString(Constant.ITEMS));


                                Gson g = new Gson();

                                for (int i = 0; i < jsonArrayItems.length(); i++) {
                                    JSONObject itemsObject = jsonArrayItems.getJSONObject(i);
                                    Items items = g.fromJson(itemsObject.toString(), Items.class);
                                    itemArrayList.add(items);

                                }
                                itemListAdapter = new ItemListAdapter(activity, itemArrayList);
                                recyclerViewItems.setAdapter(itemListAdapter);


                                lyt_order_detail.setVisibility(View.VISIBLE);

                            } else {
                                setSnackBar(activity, jsonObject.getString(Constant.MESSAGE), getString(R.string.ok), Color.RED);
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }
            }, activity, Constant.MAIN_URL, params, true);

        } else {
            setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry), Color.RED);
        }
    }

    public void Confirm_OTP() {
        final androidx.appcompat.app.AlertDialog.Builder alertDialog = new androidx.appcompat.app.AlertDialog.Builder(OrderDetailActivity.this);
        LayoutInflater inflater = (LayoutInflater) OrderDetailActivity.this.getSystemService(Context.LAYOUT_INFLATER_SERVICE);
        final View dialogView = inflater.inflate(R.layout.dialog_otp_confirm_request, null);
        alertDialog.setView(dialogView);
        alertDialog.setCancelable(true);
        final androidx.appcompat.app.AlertDialog dialog = alertDialog.create();
        Objects.requireNonNull(dialog.getWindow()).setBackgroundDrawable(new ColorDrawable(android.graphics.Color.TRANSPARENT));

        TextView tvDialogConfirm = dialogView.findViewById(R.id.tvDialogConfirm);
        TextView tvDialogCancel = dialogView.findViewById(R.id.tvDialogCancel);
        final EditText edtOTP = dialogView.findViewById(R.id.edtOTP);

        tvDialogConfirm.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {

                if (!edtOTP.getText().toString().isEmpty() || edtOTP.getText().toString().equals("0") || edtOTP.getText().toString().length() >= 6) {
                    if (checkedItem <= 3) {
                        if (checkedItem == 3) {
                            if (edtOTP.getText().toString().equals(otp)) {
                                ChangeOrderStatus(activity, (updatedStatus[0].toLowerCase()));
                                dialog.dismiss();
                            } else {
                                Toast.makeText(activity, getString(R.string.otp_not_matched), Toast.LENGTH_SHORT).show();
                            }
                        }
                    } else {
                        Toast.makeText(activity, getString(R.string.can_not_update_order), Toast.LENGTH_SHORT).show();
                    }
                } else {
                    edtOTP.setError(getString(R.string.alert_otp));
                }
            }
        });

        tvDialogCancel.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                dialog.dismiss();
            }
        });

        dialog.show();
    }

    public void OnBtnClick(View view) {

        if (AppController.isConnected(activity)) {
            int id = view.getId();
            if (id == R.id.btnCallCustomer) {
                try {
                    Intent callIntent = new Intent(Intent.ACTION_CALL);
                    if (ContextCompat.checkSelfPermission(OrderDetailActivity.this, Manifest.permission.CALL_PHONE) != PackageManager.PERMISSION_GRANTED) {
                        ActivityCompat.requestPermissions(OrderDetailActivity.this, new String[]{Manifest.permission.CALL_PHONE}, 1);
                    } else {
                        callIntent.setData(Uri.parse("tel:" + tvPhone.getText().toString().trim()));
                        startActivity(callIntent);
                    }
                } catch (Exception e) {
                    e.printStackTrace();
                }
            } else if (id == R.id.btnGetDirection) {
                AlertDialog.Builder builder1 = new AlertDialog.Builder(activity);
                builder1.setMessage(R.string.map_open_message);
                builder1.setCancelable(true);

                builder1.setPositiveButton(
                        getString(R.string.yes),
                        new DialogInterface.OnClickListener() {
                            public void onClick(DialogInterface dialog, int id) {
//                                com.google.android.apps.maps
                                try {
                                    Uri googleMapIntentUri = Uri.parse("google.navigation:q=" + latitude + "," + longitude + "");
                                    Intent mapIntent = new Intent(Intent.ACTION_VIEW, googleMapIntentUri);
                                    mapIntent.setPackage("com.google.android.apps.maps");
                                    activity.startActivity(mapIntent);
                                } catch (Exception e) {
                                    AlertDialog.Builder builder1 = new AlertDialog.Builder(activity);
                                    builder1.setMessage("Please install google map first.");
                                    builder1.setCancelable(true);
                                    builder1.setPositiveButton(
                                            getString(R.string.ok),
                                            new DialogInterface.OnClickListener() {
                                                public void onClick(DialogInterface dialog, int id) {
                                                    dialog.cancel();
                                                }
                                            });

                                    AlertDialog alert11 = builder1.create();
                                    alert11.show();
                                }
                            }
                        });

                builder1.setNegativeButton(
                        getString(R.string.no),
                        new DialogInterface.OnClickListener() {
                            public void onClick(DialogInterface dialog, int id) {
                                dialog.cancel();
                            }
                        });

                AlertDialog alert11 = builder1.create();
                alert11.show();


            } else if (id == R.id.btnDeliveryStatus) {
                // setup the alert builder
                updatedStatus = new String[1];

                AlertDialog.Builder builder = new AlertDialog.Builder(activity);
                builder.setTitle(R.string.update_status);// add a radio button list

                final String[] status = {Constant.RECEIVED, Constant.PROCESSED, Constant.SHIPPED, Constant.DELIVERED, Constant.CANCELLED, Constant.RETURNED};

                switch (btnDeliveryStatus.getText().toString()) {
                    case Constant.RECEIVED:
                        checkedItem = 0;
                        break;
                    case Constant.PROCESSED:
                        checkedItem = 1;
                        break;
                    case Constant.SHIPPED:
                        checkedItem = 2;
                        break;
                    case Constant.DELIVERED:
                        checkedItem = 3;
                        break;
                    case Constant.CANCELLED:
                        checkedItem = 4;
                        break;
                    case Constant.RETURNED:
                        checkedItem = 5;
                        break;
                }

                builder.setSingleChoiceItems(status, checkedItem, new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        checkedItem = which;
                        updatedStatus[0] = status[which];
                    }
                });
                builder.setPositiveButton(R.string.ok, new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialog, int which) {
                        Constant.CLICK = true;

                        final androidx.appcompat.app.AlertDialog.Builder alertDialog = new androidx.appcompat.app.AlertDialog.Builder(activity);
                        // Setting Dialog Message
                        alertDialog.setMessage(R.string.change_order_status_msg);
                        alertDialog.setCancelable(false);
                        final androidx.appcompat.app.AlertDialog alertDialog1 = alertDialog.create();

                        // Setting OK Button
                        alertDialog.setPositiveButton(R.string.yes, new DialogInterface.OnClickListener() {
                            public void onClick(DialogInterface dialog, int which) {
                                if (otp.equals("0")) {
                                    ChangeOrderStatus(activity, (updatedStatus[0].toLowerCase()));
                                } else {
                                    if (checkedItem == 3) {
                                        Confirm_OTP();
                                    } else {
                                        ChangeOrderStatus(activity, (updatedStatus[0].toLowerCase()));
                                    }
                                }

                                btnDeliveryStatus.setText(AppController.toTitleCase(updatedStatus[0]));
                            }
                        });
                        alertDialog.setNegativeButton(R.string.no, new DialogInterface.OnClickListener() {
                            public void onClick(DialogInterface dialog, int which) {
                                alertDialog1.dismiss();
                            }
                        });
                        // Showing Alert Message
                        alertDialog.show();

                    }
                });

                builder.setNegativeButton(R.string.cancel, null);// create and show the alert dialog
                AlertDialog dialog = builder.create();
                dialog.show();
            }
        } else {
            setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry), Color.RED);
        }

    }

    public void ChangeOrderStatus(final Activity activity, final String status) {

        if (AppController.isConnected(activity)) {

            Map<String, String> params = new HashMap<String, String>();
            params.put(Constant.DELIVERY_BOY_ID, session.getData(Constant.ID));
            params.put(Constant.ID, orderID);
            params.put(Constant.STATUS, status);
            params.put(Constant.UPDATE_ORDER_STATUS, Constant.GetVal);

            ApiConfig.RequestToVolley(new VolleyCallback() {
                @RequiresApi(api = Build.VERSION_CODES.M)
                @SuppressLint("SetTextI18n")
                @Override
                public void onSuccess(boolean result, String response) {
                    if (result) {
                        try {
                            JSONObject jsonObject = new JSONObject(response);
                            if (!jsonObject.getBoolean(Constant.ERROR)) {
                                setSnackBar(activity, jsonObject.getString(Constant.MESSAGE), getString(R.string.ok), Color.GREEN);
                                OrderList category = MainActivity.orderListArrayList.get(Constant.Position_Value);
                                category.setActive_status(status);
                                //orderList.getActive_status ()
                                Constant.CLICK = true;
                            } else {
                                setSnackBar(activity, jsonObject.getString(Constant.MESSAGE), getString(R.string.ok), Color.RED);
                            }
                        } catch (JSONException e) {
                            e.printStackTrace();
                        }
                    }
                }
            }, activity, Constant.MAIN_URL, params, true);

        } else {
            setSnackBar(activity, getString(R.string.no_internet_message), getString(R.string.retry), Color.RED);
        }
    }

    @Override
    public void onBackPressed() {
        super.onBackPressed();
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return super.onSupportNavigateUp();
    }

    public void setSnackBar(final Activity activity, String message, String action, int color) {
        final Snackbar snackbar = Snackbar.make(activity.findViewById(android.R.id.content), message, Snackbar.LENGTH_INDEFINITE);
        snackbar.setAction(action, new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                getOrderData(activity);
                snackbar.dismiss();
            }
        });

        snackbar.setActionTextColor(color);
        View snackbarView = snackbar.getView();
        TextView textView = snackbarView.findViewById(R.id.snackbar_text);
        textView.setMaxLines(5);
        snackbar.show();
    }

}