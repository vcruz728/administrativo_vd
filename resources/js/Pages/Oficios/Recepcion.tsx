import AppLayout from "../../Layouts/app";
import { Head, Link } from "@inertiajs/react";
import { useState, Fragment, useEffect } from "react";
import { Card, Row, Col, Button, Tabs, Tab } from "react-bootstrap";
import PageHeader from "../../Layouts/layoutcomponents/pageHeader";
import "filepond/dist/filepond.min.css";
// @ts-ignore
import language from "datatables.net-plugins/i18n/es-MX.mjs";
import LineaTiempo from "@/types/LineaTiempo";
import VerPdf from "@/types/VerPdf";

import $ from "jquery";
import DataTable from "datatables.net-react";
import DT from "datatables.net-bs5";

// Importa JSZip y asigna a window si es necesario
import JSZip from "jszip";
// @ts-ignore
window.JSZip = JSZip;

// Importa los botones de DataTables
import "datatables.net-buttons-bs5";
import "datatables.net-buttons/js/buttons.html5.js";

DataTable.use(DT);

export default function Recepcion({
    oficios,
    nuevoHistorico,
}: {
    oficios: [];
    nuevoHistorico: [];
}) {
    const [showLinea, setShowLinea] = useState<boolean>(false);
    const [show, setShow] = useState(false);
    const [idOficio, setIdOficio] = useState(0);
    const [variables, setVariables] = useState({
        urlPdf: "",
        extension: "",
        idOfico: 0,
    });
    const [activos, setActivos] = useState<any[]>();
    const [historico, setHistorico] = useState<any[]>();

    useEffect(() => {
        setActivos(
            (oficios || [])
                .filter((item: any) => item.archivo_respuesta === null)
                .map((file: any) => ({
                    ...file,
                }))
        );
        setHistorico(
            (oficios || [])
                .filter((item: any) => item.archivo_respuesta !== null)
                .map((file: any) => ({
                    ...file,
                }))
        );
    }, [oficios]);
    return (
        <AppLayout>
            <Head>
                <title>Listado de oficios</title>
                <meta
                    name="listado de oficios"
                    content="Visualiza la lista de oficios ingresados a la VD"
                />
            </Head>
            <Fragment>
                <PageHeader
                    titles="Listado de oficios"
                    active="Listado de oficios"
                    items={[]}
                />
                <Row>
                    <Col lg={12} md={12}>
                        <Card>
                            <Card.Header className="d-flex justify-content-between">
                                <Card.Title as="h3">
                                    Listado de oficios
                                </Card.Title>
                                <div className="tags">
                                    <span className="tag tag-radius tag-round tag-verde">
                                        Se dio respuesta en tiempo
                                    </span>

                                    <span className="tag tag-radius tag-round tag-amarillo">
                                        Sin respuesta, en tiempo
                                    </span>

                                    <span className="tag tag-radius tag-round tag-naranja">
                                        Sin respuesta, fuera de tiempo
                                    </span>

                                    <span className="tag tag-radius tag-round tag-rojo">
                                        Se dio respuesta fuera de tiempo
                                    </span>
                                </div>
                                <div className="tags"></div>
                            </Card.Header>
                            <Card.Body>
                                <div className="panel panel-default">
                                    <Tabs defaultActiveKey="tab1">
                                        <Tab eventKey="tab1" title="Activos">
                                            <Col
                                                md={12}
                                                className="d-flex justify-content-center"
                                            >
                                                <Link
                                                    className="btn btn-primary"
                                                    href={route(
                                                        "oficios.recepcionOficio"
                                                    )}
                                                >
                                                    <i className="fe fe-plus me-2"></i>
                                                    Nueva recepción
                                                </Link>
                                            </Col>
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <div className="mb-3 d-flex justify-content-end">
                                                    <button
                                                        className="btn btn-success"
                                                        onClick={() => {
                                                            const table =
                                                                $(
                                                                    "#activos-table"
                                                                ).DataTable();
                                                            table
                                                                .button(
                                                                    ".buttons-excel"
                                                                )
                                                                .trigger();
                                                        }}
                                                    >
                                                        Exportar a Excel
                                                    </button>
                                                </div>
                                                <DataTable
                                                    id="activos-table"
                                                    data={activos}
                                                    options={{
                                                        language,
                                                        order: [],
                                                        buttons: [
                                                            {
                                                                extend: "excel",
                                                                exportOptions: {
                                                                    columns: [
                                                                        1, 2, 3,
                                                                        4, 5, 6,
                                                                    ], // exporta solo las primeras 8 columnas
                                                                },
                                                            },
                                                        ],
                                                    }}
                                                    columns={[
                                                        {
                                                            data: "id",
                                                            title: "Estatus",

                                                            createdCell(
                                                                cell,
                                                                cellData,
                                                                rowData,
                                                                row,
                                                                col
                                                            ) {
                                                                $(cell).css(
                                                                    "background",
                                                                    rowData.color
                                                                );
                                                                $(cell).css(
                                                                    "color",
                                                                    rowData.color
                                                                );
                                                            },
                                                        },
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de ingreso",
                                                        },
                                                        {
                                                            data: "ingreso",
                                                            title: "Tipo de ingreso",
                                                        },
                                                        {
                                                            data: "numero_oficio",
                                                            title: "No. Oficio / Folio",
                                                        },
                                                        {
                                                            data: "des",
                                                            title: "Unidad Académica o Dependencia",
                                                        },
                                                        {
                                                            data: "area",
                                                            title: "Área responsable",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            title: "Proceso que impacta",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            title: "Acciones",
                                                        },
                                                        {
                                                            data: "descripcion",
                                                            title: "Descripción",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                    ]}
                                                    className="display table-bordered text-nowrap border-bottom"
                                                    slots={{
                                                        7: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <>
                                                                <div className="text-center">
                                                                    <Link
                                                                        className="btn-icon btn btn-warning"
                                                                        href={route(
                                                                            "oficios.modificaOficio",
                                                                            {
                                                                                id: row.id,
                                                                            }
                                                                        )}
                                                                    >
                                                                        <i className="fe fe-edit"></i>
                                                                    </Link>
                                                                    <Button
                                                                        className="btn-icon ml-1"
                                                                        variant="success"
                                                                        onClick={() => {
                                                                            setVariables(
                                                                                {
                                                                                    ...variables,
                                                                                    idOfico:
                                                                                        row.id,
                                                                                }
                                                                            );

                                                                            setShowLinea(
                                                                                true
                                                                            );
                                                                        }}
                                                                        title="Ver línea de tiempo del oficio"
                                                                    >
                                                                        <i className="fa fa-history"></i>
                                                                    </Button>
                                                                </div>
                                                            </>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                        <Tab eventKey="tab2" title="Histórico">
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <div className="mb-3 d-flex justify-content-end">
                                                    <button
                                                        className="btn btn-success"
                                                        onClick={() => {
                                                            const table =
                                                                $(
                                                                    "#historico-table"
                                                                ).DataTable();
                                                            table
                                                                .button(
                                                                    ".buttons-excel"
                                                                )
                                                                .trigger();
                                                        }}
                                                    >
                                                        Exportar a Excel
                                                    </button>
                                                </div>
                                                <DataTable
                                                    id="historico-table"
                                                    data={historico}
                                                    options={{
                                                        language,
                                                        autoWidth: false,
                                                        buttons: [
                                                            {
                                                                extend: "excel",
                                                                exportOptions: {
                                                                    columns: [
                                                                        1, 2, 3,
                                                                        4, 5,
                                                                    ], // exporta solo las primeras 8 columnas
                                                                },
                                                            },
                                                        ],
                                                    }}
                                                    columns={[
                                                        {
                                                            data: "id",
                                                            title: "Estatus",

                                                            createdCell(
                                                                cell,
                                                                cellData,
                                                                rowData,
                                                                row,
                                                                col
                                                            ) {
                                                                $(cell).css(
                                                                    "background",
                                                                    rowData.color
                                                                );
                                                                $(cell).css(
                                                                    "color",
                                                                    rowData.color
                                                                );
                                                            },
                                                            width: "5%",
                                                        },
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de ingreso",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "numero_oficio",
                                                            title: "No. Oficio / Folio",
                                                            width: "5%",
                                                        },
                                                        {
                                                            data: "area",
                                                            title: "Área",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            title: "Proceso",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "descripcion",
                                                            title: "Breve descripción",
                                                            width: "40%",
                                                        },
                                                        {
                                                            data: "proceso",
                                                            width: "15%",
                                                            title: "Acciones",
                                                        },
                                                        {
                                                            data: "descripcion",
                                                            title: "Descripción",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                        {
                                                            data: "asunto",
                                                            title: "Respuesta",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                    ]}
                                                    className="display table-bordered  border-bottom ancho100"
                                                    slots={{
                                                        6: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <div className="text-center">
                                                                <Button
                                                                    className="btn-icon btn btn-warning"
                                                                    variant="danger"
                                                                    title="Ver confirmación de recibido"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.archivo_respuesta,
                                                                                extension:
                                                                                    row.extension,
                                                                            }
                                                                        );
                                                                        setShow(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-file-pdf-o"></i>
                                                                </Button>

                                                                <Button
                                                                    className="btn-icon btn btn-warning ml-1"
                                                                    variant="danger"
                                                                    title="Ver respuesta al oficio"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.oficio_final,
                                                                                extension:
                                                                                    "pdf",
                                                                            }
                                                                        );

                                                                        setShow(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-file-pdf-o"></i>
                                                                </Button>

                                                                <Button
                                                                    className="btn-icon ml-1"
                                                                    variant="danger"
                                                                    title="Ver PDF del oficio"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                urlPdf: row.archivo,
                                                                                extension:
                                                                                    "pdf",
                                                                            }
                                                                        );

                                                                        setShow(
                                                                            true
                                                                        );
                                                                    }}
                                                                >
                                                                    <i className="fa fa-eye"></i>
                                                                </Button>

                                                                <Button
                                                                    className="btn-icon ml-1"
                                                                    variant="success"
                                                                    onClick={() => {
                                                                        setVariables(
                                                                            {
                                                                                ...variables,
                                                                                idOfico:
                                                                                    row.id,
                                                                            }
                                                                        );
                                                                        setShowLinea(
                                                                            true
                                                                        );
                                                                    }}
                                                                    title="Ver línea de tiempo del oficio"
                                                                >
                                                                    <i className="fa fa-history"></i>
                                                                </Button>
                                                            </div>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                        <Tab
                                            eventKey="tab5"
                                            title="Oficios VD Histórico"
                                        >
                                            <Col
                                                md={12}
                                                className="table-responsive"
                                            >
                                                <div className="mb-3 d-flex justify-content-end">
                                                    <button
                                                        className="btn btn-success"
                                                        onClick={() => {
                                                            const table =
                                                                $(
                                                                    "#historicoVd-table"
                                                                ).DataTable();
                                                            table
                                                                .button(
                                                                    ".buttons-excel"
                                                                )
                                                                .trigger();
                                                        }}
                                                    >
                                                        Exportar a Excel
                                                    </button>
                                                </div>
                                                <DataTable
                                                    data={nuevoHistorico}
                                                    options={{
                                                        language,
                                                        autoWidth: false,
                                                        order: [[0, "desc"]],
                                                        buttons: [
                                                            {
                                                                extend: "excel",
                                                                exportOptions: {
                                                                    columns: [
                                                                        1, 2, 3,
                                                                    ], // exporta solo las primeras 8 columnas
                                                                },
                                                            },
                                                        ],
                                                    }}
                                                    id="historicoVd-table"
                                                    columns={[
                                                        {
                                                            data: "f_ingreso",
                                                            title: "Fecha de creación",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "oficio_respuesta",
                                                            title: "Num Folio/Oficio",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "area",
                                                            title: "Área",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "nombre_desti",
                                                            title: "Destinatario",
                                                            width: "10%",
                                                        },
                                                        {
                                                            data: "id",
                                                            width: "15%",
                                                            title: "Acciones",
                                                        },
                                                        {
                                                            data: "respuesta",
                                                            title: "Respuesta",
                                                            visible: false,
                                                            searchable: true,
                                                        },
                                                    ]}
                                                    className="display table-bordered  border-bottom ancho100"
                                                    slots={{
                                                        4: (
                                                            data: any,
                                                            row: any
                                                        ) => (
                                                            <div className="text-center">
                                                                {row.masivo ==
                                                                1 ? (
                                                                    <Button
                                                                        className="btn-icon btn btn-warning mr-1"
                                                                        variant="danger"
                                                                        title="Ver confirmación de recibido"
                                                                        onClick={() => {
                                                                            setVariables(
                                                                                {
                                                                                    ...variables,
                                                                                    urlPdf: row.archivo_respuesta,
                                                                                    extension:
                                                                                        row.extension,
                                                                                }
                                                                            );
                                                                            setShow(
                                                                                true
                                                                            );
                                                                        }}
                                                                    >
                                                                        <i className="fa fa-file-pdf-o"></i>
                                                                    </Button>
                                                                ) : (
                                                                    <Link
                                                                        href={route(
                                                                            "oficios.confirmaRecibidosNuevos",
                                                                            {
                                                                                id: row.id,
                                                                            }
                                                                        )}
                                                                    >
                                                                        <Button
                                                                            className="btn-icon btn btn-warning mr-1"
                                                                            variant="warning"
                                                                            title="Confirmaciones de recibido"
                                                                        >
                                                                            <i className="fa fa-handshake-o"></i>{" "}
                                                                        </Button>
                                                                    </Link>
                                                                )}

                                                                <Link
                                                                    href={route(
                                                                        "oficios.detalleNuevo",
                                                                        {
                                                                            id: row.id,
                                                                        }
                                                                    )}
                                                                >
                                                                    <Button
                                                                        className="btn-icon "
                                                                        variant="danger"
                                                                        title="Ver detalle del oficio"
                                                                    >
                                                                        <i className="fa fa-eye"></i>
                                                                    </Button>
                                                                </Link>
                                                            </div>
                                                        ),
                                                    }}
                                                ></DataTable>
                                            </Col>
                                        </Tab>
                                    </Tabs>
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
                <LineaTiempo
                    showLinea={showLinea}
                    setShowLinea={setShowLinea}
                    id={variables.idOfico}
                />

                <VerPdf
                    urlPdf={variables.urlPdf}
                    show={show}
                    tipo={variables.extension}
                    setShow={setShow}
                />
            </Fragment>
        </AppLayout>
    );
}
