
#include <SoftwareSerial.h> //Software Serial is needed to interface with ESP8266 because the hardware serial 
//is being used for monitoring Serial line with computer during trouble shooting.
SoftwareSerial ESP8266(4,5);//RX,TX
//Serial ser;
String apiKey1 = "KNHATBG30S0OQQQF";
String id_sensor = "12345";

// Gas Sensor
#define R 10
#define G 9
#define B 8
#define MQ2 A0

int gaslevel; 
int currStat = 1;
int gasStat;

void setup() {
  Serial.begin(9600); //Initialize serial port - 9600 bps

  // put your setup code here, to run once:
  Serial.begin(115200); // this is to start serial monitoring with your Arduino IDE Serial monitor.
  ESP8266.begin(115200); // this is to start serial communication with the ESP via Software Serial.
  ESP8266.println("AT+RST"); // this resets the ESP8266.
  Serial.println("AT+RST"); // this is just echoing the command to your serial monitor so you can
  //cek 
  ESP8266.println("AT");
  if(ESP8266.find("OK")){
    Serial.println("start ..");
  }
  ESP8266.println("AT+CWMODE=3");
  ESP8266.println("AT+CIPSTO=120");
  //DISABLE DHCP
  ESP8266.println("AT+CWDHCP_CUR=1,0");
  //ESP8266.println("AT+CWJAP=\"open\",\"samocakoom\"\r\n"); // this resets the ESP8266.

  //GAS SENSOR -----------------------
  pinMode(MQ2,INPUT);
  pinMode(R,OUTPUT);
  pinMode(G,OUTPUT);
  pinMode(B,OUTPUT);

}

void loop() {
  
 ESP8266.println("AT+CIPMUX=1"); // multiple connection
  
 gaslevel=(analogRead(MQ2));
 gaslevel=map(gaslevel,0,1023,0,255);

 gasStat = aturLampuDanStatus(gaslevel);

  // everything is oke ?

  ESP8266.println("AT");
  if(ESP8266.find("OK")){
    Serial.println("ready..");
  }
  
//----------------------------------------------------------
  String id = "4";
  String server = "184.106.153.149";
   // prepare GET string
  String getStr1 = "GET /update?api_key=";
  getStr1 += apiKey1;
  getStr1 +="&field1=";
  getStr1 += String(gaslevel);
  getStr1 += "\r\n";
  
  sendESP(id, server, getStr1);

  /* send koneksi kedua */


   if(gasStat != currStat){
      if(gasStat == 1){
        
        Serial.println("normal");//print values on serial monitor       
 
      }else if(gasStat == 2){
        
        Serial.println("medium");//print values on serial monitor     
      
      }else if(gasStat == 3){
        
        Serial.println("hard");//print values on serial monitor     
      }
    
    // prepare GET string
    /* send to thinkspeak "angga kesuma"
    String getStr2 = "GET /update?api_key=";
    getStr2 += apiKey2;
    getStr2 +="&field1=";
    getStr2 += String(gaslevel);
    getStr2 += "\r\n";
    */

    
    //send to lapro
    String getStr2 = "GET /status?id_sensor=";
    getStr2 += id_sensor;
    getStr2 +="&status=";
    getStr2 += gasStat;
    getStr2 += " HTTP/1.1\r\n";
    //getStr2 += "User-Agent: curl/7.37.0\r\n";
    getStr2 += "Host: sensorgas.lapro.id\r\n";
    getStr2 += "\r\n";
    
    //kirim ke server
    sendESP("3", "sensorgas.lapro.id", getStr2);
    
    //ubah status sekarang
    currStat = gasStat;       
  }
  
}

void debugESP(){

   String com = "";

      if(ESP8266.available()) // check if the esp is sending a message 
  {
    while(ESP8266.available())
    { 
      
      // The esp has data so display its output to the serial window 
      char c = ESP8266.read(); // read the next character.
      com +=c;
    }  
    Serial.println(com);
  }
   
}

void sendESP(String id, String server, String url){
  
   // TCP connection thingspeak
  String cmd1 = "AT+CIPSTART=";
  cmd1 += id;
  cmd1 += ",\"TCP\",\"";
  cmd1 += server; // api.thingspeak.com
  //cmd1 += "member.lapro.id"; // api.thingspeak.com
  cmd1 += "\",80";
  ESP8266.println(cmd1);

  debugESP();
  
   if(ESP8266.find("Error")){
    Serial.println("AT+CIPSTART error");
    return;
  }
  


  
  // prepare GET string
  String getStr1 = url;
  //getStr1 = url;
  //getStr1 += apiKey1;
  //getStr1 +="&field1=";
  //getStr1 += "66";
  //getStr1 += "\r\n";
  
  // send data length
  cmd1 = "AT+CIPSEND=";
  cmd1 += id;
  cmd1 += ",";
  cmd1 += String(getStr1.length());
  ESP8266.println(cmd1);
   //ESP8266.println(getStr1);

  if(ESP8266.find(">")){
    ESP8266.print(getStr1);
  }
  else{
    String cipclose = "AT+CIPCLOSE=";
    cipclose += id;
    ESP8266.println(cipclose);
    // alert user
    Serial.println("AT+CIPCLOSE");
  }

  
  debugESP(); 
  
  delay(16000);  
}

int aturLampuDanStatus(int gaslevel){

  delay(1000);
  int stat = 1;
  
  if(gaslevel >= 0 && gaslevel <= 30){//gaslevel is greater than 0 and less than 30
    digitalWrite(R,LOW);//red led is off
    digitalWrite(B,LOW);//blue led is off
     _delay_ms(500);//delay
    digitalWrite(G,HIGH);//green led is on
    _delay_ms(100);
    
    //status sekarang 
    stat =1;  
  }
  else if(gaslevel > 30 && gaslevel <= 100){//gaslevel is greater than 30 and less than 110
         digitalWrite(R,LOW);//red led is off
         digitalWrite(G,LOW);//green led is off
         _delay_ms(100);
         digitalWrite(B,HIGH);//blue led is on
         //status sekarang 
         stat =2;
   }
   else if(gaslevel > 100 && gaslevel <= 255 ){//gaslevel is greater than 60 and less than 255
        digitalWrite(G,LOW);//green led is off
        digitalWrite(B,LOW);//blue led is off
        _delay_ms(100);
        digitalWrite(R,HIGH);//red led is on  
        //status sekarang 
        stat =3;
   }
   else
   {
         digitalWrite(G,LOW);//red led is off
         digitalWrite(B,LOW);//blue led is off
         digitalWrite(R,LOW);//green led is off
         //status sekarang 
         stat =1;
   }

   return stat;
}

//omo4jt4pcw ->password laproid
