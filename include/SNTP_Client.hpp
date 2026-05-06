/*
    Client SNTP pour ESP32 avec connexion Wifi active
        Classe baséé sur une structure time et les fonctions configTime()
        et getLocalTime() (esp32-hal-time.c)
    Cyril HUMBLOT
*/
#pragma once
#include <Arduino.h>
#include <time.h>

class SNTP_Client
{
private:
    tm m_datetime;
    long m_utcSec;
    int m_summerSec;
    String  m_pooladdress;
public:
    SNTP_Client(int gmtOffset=0, bool summerTime=false, String poolAddress="pool.ntp.org");
    void update();
    String getTime();
    String getDate();
    String getDateLong(bool withWeekDay=false);
};

/// @brief Constructor
/// @param utcOffset Décallage UTC
/// @param summerTime `true` Heure d'été activée
/// @param poolAddress Adresse du serveur NTPClient
SNTP_Client::SNTP_Client(int utcOffset, bool summerTime, String poolAddress)
{
    m_pooladdress=poolAddress;
    m_utcSec=3600*utcOffset;
    m_summerSec=3600*summerTime;
}

/// @brief Mise à jour de l'heure via le serveur NTP si le WiFi est connecté
void SNTP_Client::update() {
    if(WiFi.status() == WL_CONNECTED)
        configTime(m_utcSec, m_summerSec, m_pooladdress.c_str());
}

/// @brief Récupération de l'heure
/// @return String au format : HH:MM:SS
String SNTP_Client::getTime() {
    if(not getLocalTime(&m_datetime))
        return "Failed to obtain time";
    String s = m_datetime.tm_hour>9?String(m_datetime.tm_hour):"0"+String(m_datetime.tm_hour);
    s += ":" + (m_datetime.tm_min>9?String(m_datetime.tm_min):"0"+String(m_datetime.tm_min));
    s += ":" + (m_datetime.tm_sec>9?String(m_datetime.tm_sec):"0"+String(m_datetime.tm_sec));
    return s;
}

/// @brief Récupération de la date
/// @return String au format : 1/1/1970
String SNTP_Client::getDate() {
    if(not getLocalTime(&m_datetime))
        return "Failed to obtain date";
    String s = String(m_datetime.tm_mday) + "/" + String(m_datetime.tm_mon+1) + "/" + String(m_datetime.tm_year+1900);
    return s;
}

/// @brief Récupération de la date au format long
/// @param withWeekDay `true` affichage du jour de la semaine
/// @return String au format : Lundi 1 janvier 1970
String SNTP_Client::getDateLong(bool withWeekDay) {
    const String months[] = { 
        "janvier", "février", "mars", "avril", "mai", "juin", "juillet",
        "août", "septembre", "octobre", "novembre", "décembre"
    };
    const String days[] = {
        "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"
    };
    if(not getLocalTime(&m_datetime))
        return "Failed to obtain date";
    String s;
    if(withWeekDay)
        s = days[m_datetime.tm_wday] + " ";
    s += String(m_datetime.tm_mday) + " " + months[m_datetime.tm_mon] + " " + String(m_datetime.tm_year+1900);
    return s;
}